<?php

namespace App\Console\Commands;

use App\Enums\Priorities;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\BackupRestoreTest;
use App\Models\DowntimeComponent;
use App\Models\DowntimeLocation;
use App\Models\FeatureRequest;
use App\Models\User;
use App\Services\DowntimeRecordService;
use App\Services\Ops\BackupRestoreTestService;
use App\Services\ErrorReportService;
use App\Services\FeatureRequestService;
use App\Services\TicketService;
use App\Support\Backdate;
use Database\Seeders\ApplicationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Inject data harian untuk uji backup-restore.
 *
 * Tujuannya membuat database produksi selalu punya aktivitas baru pada
 * rentang jam kerja (07:00-18:00 WITA) sehingga backup harian jam 04:00
 * dan uji restore-nya selalu punya materi terbaru. Tidak memakai Faker
 * karena image prod dibangun tanpa dev dependency.
 *
 * Pemakaian:
 *   php artisan inject:daily-data --rst        # 1 catatan RST utk hari ini (idempoten)
 *   php artisan inject:daily-data --activity   # 1 data pendukung acak
 *   php artisan inject:daily-data --backfill   # isi historis --from s.d. --to (default 30 hari)
 *
 * Data dibuat atas nama user pada DAILY_DATA_USERS (env, dipisah koma).
 */
class InjectDailyDataCommand extends Command
{
    protected $signature = 'inject:daily-data
        {--rst : Buat catatan backup-restore test hari ini (idempoten per tanggal)}
        {--activity : Buat satu data pendukung acak (ticket/error/feature/downtime)}
        {--backfill : Isi historis dari --from sampai --to}
        {--from= : Tanggal mulai backfill (Y-m-d), default 30 hari lalu}
        {--to= : Tanggal akhir backfill (Y-m-d), default hari ini}';

    protected $description = 'Inject data harian uji backup-restore (RST + aktivitas tiket/error/fitur/downtime)';

    /** @var array<int, User> */
    private array $attributableUsers = [];

    public function handle(): int
    {
        if ($this->option('backfill')) {
            return $this->backfill();
        }

        if (! $this->option('rst') && ! $this->option('activity')) {
            $this->error('Tentukan --rst, --activity, atau --backfill.');

            return self::FAILURE;
        }

        if ($this->option('rst')) {
            $this->createRestoreTest(now()->startOfDay());
        }

        if ($this->option('activity')) {
            $this->createActivity(now());
        }

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Backfill historis
    // ------------------------------------------------------------------

    private function backfill(): int
    {
        $from = Carbon::parse($this->option('from') ?? now()->subDays(30)->toDateString())->startOfDay();
        $to = Carbon::parse($this->option('to') ?? now()->toDateString())->startOfDay();

        $totalRst = 0;
        $totalItems = 0;

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            if (BackupRestoreTest::query()->whereDate('test_date', $date->toDateString())->exists()) {
                continue;
            }

            $this->createRestoreTest($date->copy());
            $totalRst++;

            $itemCount = random_int(1, 4);
            for ($i = 0; $i < $itemCount; $i++) {
                $at = $this->randomWorkTime($date);
                if ($at === null) {
                    continue;
                }
                $this->createActivity($at);
                $totalItems++;
            }
        }

        $this->info("Backfill selesai: {$totalRst} catatan RST, {$totalItems} item pendukung.");

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Catatan backup-restore test
    // ------------------------------------------------------------------

    private function createRestoreTest(Carbon $date): void
    {
        if (BackupRestoreTest::query()->whereDate('test_date', $date->toDateString())->exists()) {
            $this->line("RST {$date->toDateString()} sudah ada — dilewati.");

            return;
        }

        $user = $this->pickUser();
        if ($user === null) {
            $this->warn('Tidak ada user aktif pada DAILY_DATA_USERS — RST dilewati.');

            return;
        }

        $this->ensureApplicationMaster();

        $appCode = Application::query()->where('is_active', true)->inRandomOrder()->value('code') ?? 'lainnya';
        $appName = Application::query()->where('code', $appCode)->value('name') ?? $appCode;
        $result = $this->weighted([
            'success' => 85,
            'success_with_notes' => 10,
            'failed' => 5,
        ]);

        Auth::loginUsingId($user->id);

        $record = app(BackupRestoreTestService::class)->store([
            'test_date' => $date->toDateString(),
            'performed_by' => $user->id,
            'application_system' => $appCode,
            'restore_type' => $this->weighted(['database' => 60, 'both' => 25, 'application' => 15]),
            // Jam backup asli: ±04:00 — satu-satunya waktu yang konsisten tiap hari
            'backup_datetime' => $date->copy()->setTime(4, random_int(0, 10)),
            'backup_source' => $this->weighted(['nas' => 55, 'server' => 30, 'hdd' => 15]),
            'test_environment' => $this->weighted(['server_staging' => 70, 'local_development' => 30]),
            'result' => $result,
            'notes' => $this->restoreTestNote($appName, $result),
            'follow_up' => $result === 'success' ? null : $this->restoreFollowUp($result),
        ]);

        $createdAt = $this->randomWorkTime($date) ?? now();
        $record->created_at = $createdAt;
        $record->updated_at = $createdAt;
        $record->save();

        Auth::logout();

        $this->info("RST {$record->id} dibuat ({$appName}, {$result}) atas nama {$user->email} @ {$createdAt->format('d-m-Y H:i')}");
    }

    // ------------------------------------------------------------------
    // Data pendukung: ticket / error / feature / downtime
    // ------------------------------------------------------------------

    private function createActivity(Carbon $at): void
    {
        $user = $this->pickUser();
        if ($user === null) {
            $this->warn('Tidak ada user aktif pada DAILY_DATA_USERS — aktivitas dilewati.');

            return;
        }

        $kind = $this->weighted(['ticket' => 50, 'error' => 25, 'feature' => 15, 'downtime' => 10]);

        Auth::loginUsingId($user->id);

        $label = match ($kind) {
            'ticket' => $this->createTicket($at),
            'error' => $this->createErrorReport($at),
            'feature' => $this->createFeatureRequest($at),
            'downtime' => $this->createDowntime($at),
        };

        Auth::logout();

        $this->info(ucfirst($kind)." dibuat atas nama {$user->email} @ {$at->format('d-m-Y H:i')}".($label !== null ? " — {$label}" : ''));
    }

    private function createTicket(Carbon $at): ?string
    {
        $template = $this->pickTemplate(self::TICKETS);

        $ticket = app(TicketService::class)->store([
            'title' => $template['title'],
            'description' => $template['description'],
            'category' => $template['category'],
            'priority' => $template['priority'],
        ]);

        // Layanan menetapkan waktu "sekarang"; geser ke waktu acak hari itu
        // agar riwayat terlihat alami. Due date ikut digeser sesuai SLA.
        $slaHours = Priorities::tryFrom($template['priority'])?->slaHours() ?? 48;
        $ticket->date_reported = $at;
        $ticket->due_date = $at->copy()->addHours($slaHours);
        $ticket->created_at = $at;
        $ticket->updated_at = $at;
        $ticket->save();

        Backdate::activityLogs($ticket, $at);

        return $ticket->id;
    }

    private function createErrorReport(Carbon $at): ?string
    {
        $template = $this->pickTemplate(self::ERRORS);

        $error = app(ErrorReportService::class)->store([
            'title' => $template['title'],
            'description' => $template['description'],
            'category' => $template['category'],
            'priority' => $template['priority'],
        ]);

        $error->date_reported = $at;
        $error->created_at = $at;
        $error->updated_at = $at;
        $error->save();

        Backdate::activityLogs($error, $at);
        Backdate::statusHistories($error, $at);

        return $error->id;
    }

    private function createFeatureRequest(Carbon $at): ?string
    {
        $template = $this->pickTemplate(self::FEATURES);
        $user = Auth::user();

        $feature = FeatureRequest::create([
            // feature_requests.id string (FR-YYYY-###) — wajib diisi eksplisit
            'id' => app(FeatureRequestService::class)->generateFeatureRequestId(),
            'title' => $template['title'],
            'description' => $template['description'],
            'request_type' => $template['request_type'],
            'priority' => $template['priority'],
            'status' => 'pending_approval',
            'progress' => 0,
            'reporter_id' => $user->id,
            'assigned_team' => $template['team'],
            'is_direct_input' => true,
        ]);

        $feature->created_at = $at;
        $feature->updated_at = $at;
        $feature->save();

        Backdate::activityLogs($feature, $at);
        Backdate::statusHistories($feature, $at);

        return $feature->id;
    }

    private function createDowntime(Carbon $at): ?string
    {
        $locationIds = DowntimeLocation::query()->inRandomOrder()->limit(random_int(1, 2))->pluck('id')->all();
        $componentIds = DowntimeComponent::query()->where('is_active', true)->inRandomOrder()->limit(random_int(1, 2))->pluck('id')->all();

        if ($locationIds === [] || $componentIds === []) {
            $this->warn('Master downtime kosong — downtime dilewati, diganti ticket.');
            $this->createTicket($at);

            return null;
        }

        $template = $this->pickTemplate(self::DOWNTIMES);
        $end = $at->copy()->addMinutes(random_int(45, 180));

        $record = app(DowntimeRecordService::class)->store([
            'title' => $template['title'],
            'type' => $template['type'],
            'reason' => $template['reason'],
            'description' => $template['reason'].' Pemantauan dilakukan tim IT sampai kondisi normal.',
            'start_time' => $at,
            'end_time' => $end,
            'impact' => $template['impact'],
            'location_ids' => $locationIds,
            'source_component_ids' => $componentIds,
        ]);

        $record->created_at = $at;
        $record->updated_at = $end;
        $record->save();

        Backdate::activityLogs($record, $at);

        // Notifikasi downtime yang baru dibuat ikut digeser agar feed konsisten
        DB::table('notifications')
            ->where('created_at', '>=', $at->copy()->subMinutes(1))
            ->where('created_at', '<=', now())
            ->update(['created_at' => $at]);

        return (string) $record->id;
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /**
     * Waktu acak pada jam kerja (07:00-18:00) untuk tanggal yang diberikan.
     * Untuk hari ini, waktu tidak boleh melewati sekarang. Mengembalikan
     * null bila belum ada slot yang tersisa hari ini.
     */
    private function randomWorkTime(Carbon $date): ?Carbon
    {
        $ts = $date->copy()->setTime(random_int(7, 17), random_int(0, 59), random_int(0, 59));

        if (! $date->isToday()) {
            return $ts;
        }

        if (now()->lessThan($date->copy()->setTime(7, 30))) {
            return null;
        }

        if ($ts->greaterThan(now())) {
            $ts = now()->copy()->subMinutes(random_int(2, 60));
        }

        return $ts;
    }

    /** @param array<string, int> $weights */
    private function weighted(array $weights): string
    {
        $roll = random_int(1, array_sum($weights));
        foreach ($weights as $value => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }

    /**
     * @param  list<array<string, string>> $templates
     * @return array<string, string>
     */
    private function pickTemplate(array $templates): array
    {
        return $templates[array_rand($templates)];
    }

    private function pickUser(): ?User
    {
        if ($this->attributableUsers === []) {
            $emails = collect(explode(',', (string) env('DAILY_DATA_USERS', 'alfa@gmail.com,apin@gmail.com,reynerneo08@gmail.com')))
                ->map(fn (string $email) => trim($email))
                ->filter();

            $this->attributableUsers = User::query()
                ->whereIn('email', $emails)
                ->where('is_active', true)
                ->get()
                ->all();
        }

        return $this->attributableUsers === []
            ? null
            : $this->attributableUsers[array_rand($this->attributableUsers)];
    }

    private function ensureApplicationMaster(): void
    {
        if (Application::query()->exists()) {
            return;
        }

        foreach (ApplicationSeeder::items() as $item) {
            Application::query()->updateOrCreate(
                ['code' => $item['code']],
                [...$item, 'is_active' => true]
            );
        }

        $this->line('Master aplikasi kosong — diisi otomatis dari ApplicationSeeder.');
    }

    private function restoreTestNote(string $appName, string $result): string
    {
        $notes = match ($result) {
            'failed' => [
                "Restore {$appName} gagal pada tahap import database (dump terpotong).",
                "Restore {$appName} berhenti di 70%, proses dihentikan sementara.",
                "Restore {$appName} gagal, file backup tidak dapat dibuka.",
            ],
            'success_with_notes' => [
                "Restore {$appName} berhasil namun lambat (±40 menit), perlu optimasi tahap import.",
                "Restore {$appName} sukses dengan catatan: 2 indeks perlu dibuat ulang manual.",
                "Restore {$appName} berhasil, sebagian konfigurasi harus disetel ulang manual.",
            ],
            default => [
                "Restore {$appName} ke server staging berhasil, login dan transaksi utama terverifikasi.",
                "Uji restore database {$appName} dari backup pagi ini, jumlah record sesuai backup terakhir.",
                "Restore {$appName} berjalan normal, tidak ditemukan data yang hilang.",
                "Restore {$appName} selesai, integritas tabel utama sudah dicek.",
            ],
        };

        return $notes[array_rand($notes)];
    }

    private function restoreFollowUp(string $result): string
    {
        $followUps = $result === 'failed'
            ? [
                'Ulangi restore dengan dump terbaru dan verifikasi ukuran file backup.',
                'Cek integritas file backup pada NAS, lalu ulangi proses pada jendela malam.',
                'Koordinasi dengan vendor untuk analisis kegagalan import database.',
            ]
            : [
                'Pantau durasi restore pada pengujian berikutnya.',
                'Jadwalkan pembuatan ulang indeks pada jendela maintenance.',
                'Dokumentasikan langkah penyetelan manual ke SOP restore.',
            ];

        return $followUps[array_rand($followUps)];
    }

    // ------------------------------------------------------------------
    // Template konten (tanpa Faker — image prod tidak punya dev dependency)
    // ------------------------------------------------------------------

    private const TICKETS = [
        ['title' => 'Printer antrean loket 3 tidak mencetak nomor', 'description' => 'Printer thermal antrean di loket 3 menyala tetapi tidak mencetak nomor pasien. Kabel data sudah dicek dan terpasang baik.', 'category' => 'hardware_problem', 'priority' => 'high'],
        ['title' => 'Bridging BPJS error saat cek kepesertaan', 'description' => 'Saat verifikasi kepesertaan BPJS muncul error koneksi ke server BPJS sejak pagi. Beberapa pasien harus dicatat manual.', 'category' => 'system_error', 'priority' => 'critical'],
        ['title' => 'RME lambat dibuka di poli gigi', 'description' => 'Aplikasi RME membutuhkan lebih dari satu menit untuk membuka rekam medis pasien di poli gigi.', 'category' => 'performance_issue', 'priority' => 'medium'],
        ['title' => 'Nomor antrean online tidak terkirim ke display', 'description' => 'Pasien yang mengambil antrean online tidak muncul di display TV ruang tunggu.', 'category' => 'software_bug', 'priority' => 'high'],
        ['title' => 'Wi-Fi poli terputus berkala', 'description' => 'Koneksi Wi-Fi di area poli sering terputus setiap beberapa menit.', 'category' => 'network_issue', 'priority' => 'medium'],
        ['title' => 'SATUSEHAT gagal kirim data laboratorium', 'description' => 'Pengiriman hasil laboratorium ke SATUSEHAT gagal dengan respons timeout.', 'category' => 'system_error', 'priority' => 'critical'],
        ['title' => 'Komputer pendaftaran UGD blue screen', 'description' => 'PC pendaftaran UGD mengalami blue screen berulang saat membuka aplikasi pendaftaran.', 'category' => 'hardware_problem', 'priority' => 'high'],
        ['title' => 'Cetak laporan harian farmasi lambat', 'description' => 'Proses cetak rekap harian farmasi memakan waktu lebih dari lima menit.', 'category' => 'performance_issue', 'priority' => 'low'],
        ['title' => 'Login SIMRS ditolak untuk user baru', 'description' => 'User baru yang dibuat admin tidak bisa login, muncul pesan kredensial salah meski password benar.', 'category' => 'software_bug', 'priority' => 'medium'],
        ['title' => 'Scanner kartu asuransi tidak terdeteksi', 'description' => 'Scanner dokumen di loket asuransi tidak terdeteksi setelah PC direstart.', 'category' => 'hardware_problem', 'priority' => 'low'],
        ['title' => 'Nomor antrean ganda di display UGD', 'description' => 'Display UGD menampilkan dua nomor yang sama untuk pasien berbeda.', 'category' => 'software_bug', 'priority' => 'high'],
        ['title' => 'Kabel LAN ruang radiologi putus', 'description' => 'Konektor LAN di ruang radiologi longgar sehingga PC sering lost connection.', 'category' => 'network_issue', 'priority' => 'medium'],
        ['title' => 'Notifikasi email tiket tidak terkirim', 'description' => 'Email notifikasi tiket baru tidak sampai ke inbox pic unit sejak kemarin.', 'category' => 'system_error', 'priority' => 'medium'],
        ['title' => 'Usulan kolom asal rujukan pada form tiket', 'description' => 'Mohon ditambahkan pilihan asal rujukan (IGD/poli/external) pada form tiket baru.', 'category' => 'feature_request', 'priority' => 'low'],
    ];

    private const ERRORS = [
        ['title' => 'Server antrean mati mendadak', 'description' => 'Server aplikasi antrean mati total dan tidak merespons ping.', 'category' => 'hardware', 'priority' => 'critical'],
        ['title' => 'Aplikasi lab error 500 saat input hasil', 'description' => 'Input hasil pemeriksaan lab selalu menghasilkan error 500 pada pasien tertentu.', 'category' => 'software', 'priority' => 'high'],
        ['title' => 'Switch distribusi poli down', 'description' => 'Switch di lantai poli tidak menyala, seluruh PC poli offline dari jaringan.', 'category' => 'network', 'priority' => 'high'],
        ['title' => 'Database RME lock table berulang', 'description' => 'Tabel rekam medis sering terkunci saat beberapa petugas membuka pasien yang sama.', 'category' => 'software', 'priority' => 'medium'],
        ['title' => 'UPS ruang server berbunyi terus', 'description' => 'UPS utama berbunyi beep terus-menerus, indikator baterai merah.', 'category' => 'hardware', 'priority' => 'medium'],
        ['title' => 'VPN ke gudang rekam terputus', 'description' => 'Tunnel VPN menuju gudang rekam medis tidak kunjung tersambung.', 'category' => 'network', 'priority' => 'low'],
        ['title' => 'Bridging SATUSEHAT timeout', 'description' => 'Semua request ke endpoint SATUSEHAT timeout lebih dari 30 detik.', 'category' => 'software', 'priority' => 'critical'],
        ['title' => 'Layar touchscreen kiosk mati', 'description' => 'Layar sentuh kiosk pengambilan antrean mandiri mati total.', 'category' => 'hardware', 'priority' => 'low'],
        ['title' => 'Koneksi internet utama fluktuatif', 'description' => 'Link internet utama naik turun, sesi pengguna sering terputus.', 'category' => 'network', 'priority' => 'medium'],
        ['title' => 'Error cetak gelang pasien bayi', 'description' => 'Pencetakan gelang identitas bayi gagal dengan pesan driver error.', 'category' => 'software', 'priority' => 'low'],
    ];

    private const FEATURES = [
        ['title' => 'Tambah filter poli pada laporan antrean', 'description' => 'Laporan antrean harian mohon ditambah filter per poli agar rekap lebih rapi.', 'request_type' => 'feature_request', 'priority' => 'medium', 'team' => 'programmer'],
        ['title' => 'Export rekap downtime ke Excel', 'description' => 'Butuh tombol export rekap downtime bulanan ke format Excel untuk laporan manajemen.', 'request_type' => 'feature_request', 'priority' => 'low', 'team' => 'programmer'],
        ['title' => 'Notifikasi WhatsApp untuk tiket kritis', 'description' => 'Tiket berprioritas critical mohon dikirim juga via WhatsApp ke pic unit.', 'request_type' => 'feature_request', 'priority' => 'high', 'team' => 'network'],
        ['title' => 'Dashboard monitoring uptime per aplikasi', 'description' => 'Dashboard uptime per aplikasi (SIMRS/RME/antrean) agar mudah dipantau harian.', 'request_type' => 'feature_request', 'priority' => 'medium', 'team' => 'programmer'],
        ['title' => 'Perbaiki validasi nomor BPJS di form', 'description' => 'Validasi nomor kartu BPJS masih menerima 12 digit, seharusnya 13.', 'request_type' => 'bug_fix', 'priority' => 'high', 'team' => 'programmer'],
        ['title' => 'Perbaiki urutan kolom tabel tiket', 'description' => 'Kolom prioritas dan status tertukar setelah update terakhir.', 'request_type' => 'bug_fix', 'priority' => 'low', 'team' => 'programmer'],
        ['title' => 'Integrasi cetak label specimen lab', 'description' => 'Mohon integrasi cetak label specimen dari aplikasi laboratorium.', 'request_type' => 'feature_request', 'priority' => 'medium', 'team' => 'hardware'],
        ['title' => 'Perbaiki hitungan SLA pada akhir pekan', 'description' => 'SLA ikut menghitung Sabtu-Minggu sehingga due date meleset.', 'request_type' => 'bug_fix', 'priority' => 'medium', 'team' => 'programmer'],
    ];

    private const DOWNTIMES = [
        ['title' => 'Gangguan jaringan poli dan kasir', 'reason' => 'Switch distribusi restart berulang sehingga koneksi PC poli dan kasir terputus.', 'type' => 'unplanned', 'impact' => 'high'],
        ['title' => 'Maintenance terjadwal server SIMRS', 'reason' => 'Patching OS dan update antivirus pada server utama SIMRS.', 'type' => 'planned', 'impact' => 'medium'],
        ['title' => 'Antrean online tidak dapat diakses pasien', 'reason' => 'Layanan antrean web down setelah pembaruan aplikasi.', 'type' => 'unplanned', 'impact' => 'critical'],
        ['title' => 'Internet utama lambat sejak pagi', 'reason' => 'Bandwidth ISP fluktuatif, lalu lintas dialihkan sementara ke link backup.', 'type' => 'unplanned', 'impact' => 'medium'],
        ['title' => 'Maintenance bridging BPJS oleh vendor', 'reason' => 'Vendor melakukan maintenance rutin server BPJS sesuai pengumuman.', 'type' => 'planned', 'impact' => 'low'],
        ['title' => 'SIMRS tidak bisa diakses seluruh poli', 'reason' => 'Service database berhenti, dilakukan restart manual oleh tim IT.', 'type' => 'unplanned', 'impact' => 'critical'],
        ['title' => 'Gangguan listrik gedung lama', 'reason' => 'Pasokan listrik padam, UPS bertahan sampai genset menyala.', 'type' => 'unplanned', 'impact' => 'high'],
        ['title' => 'Upgrade storage server RME', 'reason' => 'Penambahan kapasitas disk pada server RME sesuai rencana tahunan.', 'type' => 'planned', 'impact' => 'low'],
    ];
}
