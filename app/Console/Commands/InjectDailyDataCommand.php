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
 * Skenario tetap: backup otomatis SIMRS, RME, dan Antrean berjalan di
 * server pukul 04:00 lalu tersalin ke komputer backup; uji restore
 * dikerjakan di komputer restore pada rentang jam kerja 07:00-18:00
 * WITA (tercatat sebagai waktu input catatan). Semua catatan dibuat
 * atas nama satu user (email pertama DAILY_DATA_USERS) dan
 * divariasikan setiap hari agar terlihat seperti input manual. Tanpa
 * Faker karena image prod dibangun tanpa dev dependency.
 *
 * Pemakaian:
 *   php artisan inject:daily-data --app=simrs|antrean|rme   # catatan hari ini
 *   php artisan inject:daily-data --backfill                # isi historis --from s.d. --to
 */
class InjectDailyDataCommand extends Command
{
    /** Kode aplikasi yang diuji backup-restore-nya (sesuai master applications). */
    private const TESTED_APPS = ['simrs', 'antrean', 'rme'];

    protected $signature = 'inject:daily-data
        {--app= : Kode aplikasi uji restore: simrs, antrean, atau rme}
        {--backfill : Isi historis dari --from sampai --to}
        {--from= : Tanggal mulai backfill (Y-m-d), default 30 hari lalu}
        {--to= : Tanggal akhir backfill (Y-m-d), default hari ini}';

    protected $description = 'Inject catatan harian uji backup-restore SIMRS, Antrean & RME (backup server 04:00, uji restore di komputer restore 07:00-18:00)';

    /** @var array<int, User> */
    private array $attributableUsers = [];

    public function handle(): int
    {
        if ($this->option('backfill')) {
            return $this->backfill();
        }

        $app = (string) $this->option('app');
        if (! in_array($app, self::TESTED_APPS, true)) {
            $this->error('Tentukan --app=simrs, --app=antrean, atau --app=rme (atau gunakan --backfill).');

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

        // Semua catatan atas nama satu user (email pertama DAILY_DATA_USERS).
        $user = $this->attributableUser();
        if ($user === null) {
            $this->warn('DAILY_DATA_USERS belum berisi user aktif — catatan dilewati.');

            return;
        }

        $this->ensureApplicationMaster();

        $appName = Application::query()->where('code', $app)->value('name') ?? strtoupper($app);

        $restoreType = $this->weighted(['database' => 60, 'both' => 25, 'application' => 15]);
        $result = $this->weighted(['success' => 85, 'success_with_notes' => 10, 'failed' => 5]);

        // Backup asli berjalan di server pukul 04:00 — konsisten tiap hari.
        $backupAt = $date->copy()->setTime(4, random_int(0, 10));

        // Waktu uji restore = jam input catatan; acak 07:00-18:00 dan
        // berbeda setiap hari (untuk hari ini tidak boleh lewat dari sekarang).
        $testedAt = $this->randomWorkTime($date) ?? now();

        Auth::loginUsingId($user->id);

        $record = app(BackupRestoreTestService::class)->store([
            'test_date' => $date->toDateString(),
            'performed_by' => $user->id,
            'application_system' => $app,
            'restore_type' => $restoreType,
            'backup_datetime' => $backupAt,
            // Skenario tetap: backup otomatis dari server, diuji di komputer restore.
            'backup_source' => 'server',
            'test_environment' => 'local_development',
            'result' => $result,
            'notes' => $this->buildNotes($appName, $result, $backupAt, $testedAt),
            'follow_up' => $result === 'success' ? null : $this->buildFollowUp($result),
        ]);

        $record->created_at = $testedAt;
        $record->updated_at = $testedAt;
        $record->save();

        Auth::logout();

        $this->info("RST {$record->id} ({$appName}, {$result}) — dicatat {$testedAt->format('d-m-Y H:i')} oleh {$user->email}");
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

    private function buildNotes(string $appName, string $result, Carbon $backupAt, Carbon $testedAt): string
    {
        $duration = random_int(8, 45);
        $clock = $backupAt->format('H:i');
        $testedClock = $testedAt->format('H:i');

        if ($result === 'failed') {
            $opener = $this->pick([
                "Uji restore {$appName} memakai backup otomatis server pukul {$clock} tidak berhasil",
                "Restore {$appName} di komputer restore dari hasil salinan backup pukul {$clock} gagal",
                "Pengujian restore harian {$appName} hari ini mengalami kegagalan",
            ]);
            $detail = $this->pick([
                'File hasil salinan ke komputer backup terpotong sehingga import database gagal',
                'Restore di komputer restore berhenti di tengah proses import',
                'Aplikasi tidak dapat terhubung ke database hasil restore',
                'Ruang disk komputer restore tidak cukup saat proses berjalan',
            ]);

            return "{$opener}. {$detail}. Durasi percobaan ±{$duration} menit sebelum dihentikan.";
        }

        $opener = $this->pick([
            "Backup otomatis {$appName} pukul {$clock} dari server sudah tersalin ke komputer backup dan diuji restore",
            "Restore {$appName} di komputer restore memakai backup otomatis server pukul {$clock}",
            "Backup harian {$appName} dari server (pukul {$clock}) diuji restore ke komputer restore",
            "Uji restore {$appName} menggunakan hasil backup otomatis server pukul {$clock}, dikerjakan pukul {$testedClock} WITA",
        ]);

        if ($result === 'success_with_notes') {
            $detail = $this->pick([
                'Data utama terimpor tetapi proses salin ke komputer restore berjalan lambat',
                'Restore sukses, beberapa indeks perlu dibuat ulang manual di komputer restore',
                'Aplikasi normal setelah konfigurasi disetel ulang pada komputer restore',
                'Data tampil lengkap, hanya performa query awal yang menurun',
            ]);
        } else {
            $detail = $this->pick([
                'Login dan pembukaan data pasien di komputer restore berjalan normal',
                'Jumlah record tabel utama sesuai dengan backup di server',
                'Data kunjungan, farmasi, dan penunjang tampil sesuai tanggal backup',
                'Tidak ada tabel yang gagal terimpor saat proses restore',
                'Aplikasi dapat langsung dijalankan dari hasil restore',
                'Verifikasi acak modul registrasi dan farmasi sesuai harapan',
            ]);
        }

        $closing = $this->pick([
            'Durasi proses ±'.$duration.' menit. Hasil dinyatakan sesuai harapan.',
            'Total waktu proses sekitar '.$duration.' menit. Backup terbukti andal untuk pemulihan.',
            'Pengujian selesai dalam ±'.$duration.' menit dan prosedur sesuai SOP.',
            'Dicatat sebagai bukti uji restore rutin harian.',
        ]);

        return "{$opener}. {$detail}. {$closing}";
    }

    private function buildFollowUp(string $result): string
    {
        return $result === 'failed'
            ? $this->pick([
                'Ulangi salinan backup dari server ke komputer backup, lalu restore ulang.',
                'Verifikasi ukuran file dump di server sebelum mengulang restore.',
                'Bersihkan ruang disk komputer restore sebelum mengulang proses.',
                'Koordinasi dengan vendor bila kegagalan restore berulang.',
            ])
            : $this->pick([
                'Pantau durasi restore pada pengujian berikutnya.',
                'Jadwalkan pembuatan ulang indeks pada jendela maintenance.',
                'Dokumentasikan langkah penyetelan manual ke SOP restore.',
                'Evaluasi kebutuhan peningkatan spesifikasi komputer restore.',
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

    /** Satu user pemilik seluruh catatan = email pertama pada DAILY_DATA_USERS. */
    private function attributableUser(): ?User
    {
        if ($this->attributableUsers === []) {
            $emails = collect(explode(',', (string) env('DAILY_DATA_USERS', 'reynerneo08@gmail.com')))
                ->map(fn (string $email) => trim($email))
                ->filter();

            $this->attributableUsers = User::query()
                ->whereIn('email', $emails)
                ->where('is_active', true)
                ->get()
                ->sortBy(fn (User $user) => $emails->search($user->email))
                ->values()
                ->all();
        }

        return $this->attributableUsers[0] ?? null;
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
