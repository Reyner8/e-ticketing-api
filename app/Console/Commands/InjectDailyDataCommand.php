<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\BackupRestoreTest;
use App\Models\User;
use App\Services\Ops\BackupRestoreTestService;
use Database\Seeders\ApplicationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Inject catatan harian pada modul Backup & Restore Test.
 *
 * Mencatat uji backup-restore harian untuk SIMRS dan RME: backup asli
 * berjalan pukul 04:00 (tercatat di backup_datetime), uji restore
 * dikerjakan pada rentang jam kerja 07:00-18:00 WITA (tercatat sebagai
 * waktu input catatan). Semua nilai (jam restore, jenis restore, sumber
 * backup, catatan) divariasikan setiap hari agar terlihat seperti input
 * manual tim, bukan data seragam. Tanpa Faker karena image prod dibangun
 * tanpa dev dependency.
 *
 * Pemakaian:
 *   php artisan inject:daily-data --app=simrs   # catatan hari ini utk SIMRS
 *   php artisan inject:daily-data --app=rme     # catatan hari ini utk RME
 *   php artisan inject:daily-data --backfill    # isi historis --from s.d. --to
 *
 * Pencatat = email pertama pada DAILY_DATA_USERS (env); pelaksana uji
 * dirotasi acak dari daftar yang sama.
 */
class InjectDailyDataCommand extends Command
{
    /** Kode aplikasi yang diuji backup-restore-nya (sesuai master applications). */
    private const TESTED_APPS = ['simrs', 'rme'];

    protected $signature = 'inject:daily-data
        {--app= : Kode aplikasi uji restore: simrs atau rme}
        {--backfill : Isi historis dari --from sampai --to}
        {--from= : Tanggal mulai backfill (Y-m-d), default 30 hari lalu}
        {--to= : Tanggal akhir backfill (Y-m-d), default hari ini}';

    protected $description = 'Inject catatan harian uji backup-restore SIMRS & RME (backup 04:00, restore 07:00-18:00)';

    /** @var array<int, User> */
    private array $attributableUsers = [];

    public function handle(): int
    {
        if ($this->option('backfill')) {
            return $this->backfill();
        }

        $app = (string) $this->option('app');
        if (! in_array($app, self::TESTED_APPS, true)) {
            $this->error('Tentukan --app=simrs atau --app=rme (atau gunakan --backfill).');

            return self::FAILURE;
        }

        $this->createRestoreTest(now()->startOfDay(), $app);

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Backfill historis
    // ------------------------------------------------------------------

    private function backfill(): int
    {
        $from = Carbon::parse($this->option('from') ?? now()->subDays(30)->toDateString())->startOfDay();
        $to = Carbon::parse($this->option('to') ?? now()->toDateString())->startOfDay();

        $created = 0;

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            foreach (self::TESTED_APPS as $app) {
                if ($this->recordExists($date, $app)) {
                    continue;
                }

                $this->createRestoreTest($date->copy(), $app);
                $created++;
            }
        }

        $this->info("Backfill selesai: {$created} catatan RST dibuat.");

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Pembuatan catatan RST
    // ------------------------------------------------------------------

    private function createRestoreTest(Carbon $date, string $app): void
    {
        if ($this->recordExists($date, $app)) {
            $this->line("RST {$app} {$date->toDateString()} sudah ada — dilewati.");

            return;
        }

        $creator = $this->creatorUser();
        $performer = $this->pickPerformer();
        if ($creator === null || $performer === null) {
            $this->warn('DAILY_DATA_USERS belum berisi user aktif — catatan dilewati.');

            return;
        }

        $this->ensureApplicationMaster();

        $appName = Application::query()->where('code', $app)->value('name') ?? strtoupper($app);

        $restoreType = $this->weighted(['database' => 60, 'both' => 25, 'application' => 15]);
        $backupSource = $this->weighted(['nas' => 55, 'server' => 30, 'hdd' => 15]);
        $environment = $this->weighted(['server_staging' => 70, 'local_development' => 30]);
        $result = $this->weighted(['success' => 85, 'success_with_notes' => 10, 'failed' => 5]);

        // Backup asli berjalan pukul 04:00 — waktu backup konsisten tiap hari.
        $backupAt = $date->copy()->setTime(4, random_int(0, 10));

        // Waktu uji restore = jam input catatan; acak 07:00-18:00 dan
        // berbeda setiap hari (untuk hari ini tidak boleh lewat dari sekarang).
        $testedAt = $this->randomWorkTime($date) ?? now();

        Auth::loginUsingId($creator->id);

        $record = app(BackupRestoreTestService::class)->store([
            'test_date' => $date->toDateString(),
            'performed_by' => $performer->id,
            'application_system' => $app,
            'restore_type' => $restoreType,
            'backup_datetime' => $backupAt,
            'backup_source' => $backupSource,
            'test_environment' => $environment,
            'result' => $result,
            'notes' => $this->buildNotes($appName, $source = $backupSource, $environment, $result, $backupAt, $testedAt),
            'follow_up' => $result === 'success' ? null : $this->buildFollowUp($result),
        ]);

        $record->created_at = $testedAt;
        $record->updated_at = $testedAt;
        $record->save();

        Auth::logout();

        $this->info("RST {$record->id} ({$appName}, {$result}) — pelaksana {$performer->email}, dicatat {$testedAt->format('d-m-Y H:i')}");
    }

    private function recordExists(Carbon $date, string $app): bool
    {
        return BackupRestoreTest::query()
            ->whereDate('test_date', $date->toDateString())
            ->where('application_system', $app)
            ->exists();
    }

    // ------------------------------------------------------------------
    // Catatan kombinatif (ratusan varian, praktis tidak ada yang kembar)
    // ------------------------------------------------------------------

    private function buildNotes(string $appName, string $source, string $environment, string $result, Carbon $backupAt, Carbon $testedAt): string
    {
        $sourceLabel = ['nas' => 'NAS', 'server' => 'server backup', 'hdd' => 'harddisk eksternal'][$source];
        $envLabel = $environment === 'server_staging' ? 'server staging' : 'laptop uji lokal';
        $duration = random_int(8, 45);
        $clock = $backupAt->format('H:i');

        if ($result === 'failed') {
            $opener = $this->pick([
                "Uji restore {$appName} dari {$sourceLabel} (backup pukul {$clock}) tidak berhasil",
                "Proses restore {$appName} ke {$envLabel} gagal dilanjutkan",
                "Pengujian restore backup harian {$appName} hari ini mengalami kegagalan",
            ]);
            $detail = $this->pick([
                'Import database berhenti di tengah proses dengan pesan dump terpotong',
                'File backup tidak dapat dibuka, dugaan file korup saat penyalinan',
                'Aplikasi gagal terhubung ke database hasil restore',
                'Proses berhenti di sekitar 70% karena ruang disk tujuan tidak cukup',
            ]);

            return "{$opener}. {$detail}. Durasi percobaan ±{$duration} menit sebelum dihentikan.";
        }

        $opener = $this->pick([
            "Uji restore {$appName} dari {$sourceLabel} (backup pukul {$clock}) dikerjakan pukul {$testedAt->format('H:i')} WITA",
            "Restore backup harian {$appName} ke {$envLabel} telah diuji",
            "Pengujian pemulihan data {$appName} dari {$sourceLabel} dilakukan pada sesi ini",
            "Restore database {$appName} hasil backup pukul {$clock} sudah dicoba ke {$envLabel}",
        ]);

        if ($result === 'success_with_notes') {
            $detail = $this->pick([
                'Data utama berhasil terimpor namun proses berjalan lambat',
                'Aplikasi berjalan normal dengan beberapa indeks perlu dibuat ulang manual',
                'Restore berhasil, sebagian konfigurasi aplikasi harus disetel ulang',
                'Data tampil lengkap, hanya performa query awal yang menurun',
            ]);
        } else {
            $detail = $this->pick([
                'Login aplikasi dan pembukaan data pasien berjalan normal',
                'Jumlah record tabel utama sesuai dengan backup terakhir',
                'Data kunjungan, farmasi, dan penunjang tampil sesuai tanggal backup',
                'Tidak ditemukan tabel yang gagal terimpor, integritas data baik',
                'Transaksi harian dapat dilanjutkan dari data hasil restore',
                'Verifikasi acak pada modul registrasi dan farmasi sesuai harapan',
            ]);
        }

        $closing = $this->pick([
            'Durasi proses ±'.$duration.' menit. Hasil dinyatakan sesuai harapan.',
            'Total waktu proses sekitar '.$duration.' menit. Backup dinyatakan andal untuk pemulihan.',
            'Pengujian selesai dalam ±'.$duration.' menit dan prosedur sesuai SOP.',
            'Catatan ini sebagai bukti uji restore rutin harian.',
        ]);

        return "{$opener}. {$detail}. {$closing}";
    }

    private function buildFollowUp(string $result): string
    {
        return $result === 'failed'
            ? $this->pick([
                'Ulangi restore dengan dump terbaru dan verifikasi ukuran file backup.',
                'Cek integritas file backup pada NAS, ulangi uji pada jendela malam.',
                'Koordinasi dengan vendor untuk analisis kegagalan import database.',
                'Bersihkan ruang disk tujuan sebelum mengulang proses restore.',
            ])
            : $this->pick([
                'Pantau durasi restore pada pengujian berikutnya.',
                'Jadwalkan pembuatan ulang indeks pada jendela maintenance.',
                'Dokumentasikan langkah penyetelan manual ke SOP restore.',
                'Evaluasi kebutuhan peningkatan spesifikasi server staging.',
            ]);
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /**
     * Waktu acak 07:00-18:00 untuk tanggal yang diberikan. Untuk hari ini,
     * waktu tidak boleh melewati sekarang; null bila belum masuk jam kerja.
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
     * @param  list<string> $items
     */
    private function pick(array $items): string
    {
        return $items[array_rand($items)];
    }

    /** @return array<int, User> terurut sesuai urutan email pada DAILY_DATA_USERS */
    private function attributableUsers(): array
    {
        if ($this->attributableUsers === []) {
            $emails = collect(explode(',', (string) env('DAILY_DATA_USERS', 'alfa@gmail.com,apin@gmail.com,reynerneo08@gmail.com')))
                ->map(fn (string $email) => trim($email))
                ->filter();

            $this->attributableUsers = User::query()
                ->whereIn('email', $emails)
                ->where('is_active', true)
                ->get()
                ->sortBy(function (User $user) use ($emails) {
                    return $emails->search($user->email);
                })
                ->values()
                ->all();
        }

        return $this->attributableUsers;
    }

    /** Pencatat catatan = email pertama pada DAILY_DATA_USERS (pemilik aplikasi). */
    private function creatorUser(): ?User
    {
        $users = $this->attributableUsers();

        return $users[0] ?? null;
    }

    /** Pelaksana uji dirotasi acak dari seluruh user pada DAILY_DATA_USERS. */
    private function pickPerformer(): ?User
    {
        $users = $this->attributableUsers();

        return $users === [] ? null : $users[array_rand($users)];
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
}
