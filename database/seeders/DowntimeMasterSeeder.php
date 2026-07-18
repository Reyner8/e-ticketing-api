<?php

namespace Database\Seeders;

use App\Enums\DowntimeComponentCategory;
use App\Models\DowntimeComponent;
use App\Models\DowntimeLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Master data downtime untuk konteks rumah sakit.
 *
 * Aman dijalankan ulang (idempotent):
 * - Lokasi & komponen di-upsert berdasarkan `code`.
 * - Sisa master lama yang tidak dipakai riwayat downtime dihapus; yang masih
 *   dipakai dibiarkan agar riwayat tetap valid.
 * - Peta dependency dibangun ulang penuh (tabel mapping, bukan data riwayat).
 *
 * Jalankan: php artisan db:seed --class=DowntimeMasterSeeder
 */
class DowntimeMasterSeeder extends Seeder
{
    /**
     * @return array<int, array{code: string, name: string, description: string}>
     */
    public static function locations(): array
    {
        return [
            ['code' => 'igd', 'name' => 'IGD', 'description' => 'Instalasi Gawat Darurat'],
            ['code' => 'rawat-jalan', 'name' => 'Rawat Jalan', 'description' => 'Poliklinik rawat jalan'],
            ['code' => 'rawat-inap', 'name' => 'Rawat Inap', 'description' => 'Bangsal rawat inap'],
            ['code' => 'icu', 'name' => 'ICU / HCU', 'description' => 'Perawatan intensif'],
            ['code' => 'ok', 'name' => 'Instalasi Bedah (OK)', 'description' => 'Kamar operasi'],
            ['code' => 'farmasi', 'name' => 'Instalasi Farmasi', 'description' => 'Apotek / farmasi'],
            ['code' => 'lab', 'name' => 'Laboratorium', 'description' => 'Laboratorium klinik'],
            ['code' => 'radiologi', 'name' => 'Radiologi', 'description' => 'Instalasi radiologi'],
            ['code' => 'pendaftaran', 'name' => 'Pendaftaran & Kasir', 'description' => 'Front office pendaftaran dan kasir'],
            ['code' => 'rekam-medis', 'name' => 'Rekam Medis', 'description' => 'Unit rekam medis'],
            ['code' => 'server-room', 'name' => 'Ruang Server', 'description' => 'Data center / ruang server'],
            ['code' => 'manajemen', 'name' => 'Gedung Manajemen', 'description' => 'Kantor administrasi / manajemen'],
        ];
    }

    /**
     * @return array<int, array{code: string, name: string, category: string, description: string}>
     */
    public static function components(): array
    {
        return [
            // Application
            ['code' => 'simrs', 'name' => 'SIMRS', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Sistem Informasi Manajemen Rumah Sakit'],
            ['code' => 'rme', 'name' => 'RME', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Rekam Medis Elektronik'],
            ['code' => 'antrean', 'name' => 'Aplikasi Antrean', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Aplikasi antrean pasien'],
            ['code' => 'bridging-bpjs', 'name' => 'Bridging BPJS', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Integrasi BPJS (VClaim / Aplicares)'],
            ['code' => 'satusehat', 'name' => 'Integrasi SATUSEHAT', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Integrasi platform SATUSEHAT'],
            ['code' => 'sim-farmasi', 'name' => 'SIM Farmasi', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Aplikasi farmasi / apotek'],
            ['code' => 'lis', 'name' => 'LIS', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Laboratory Information System'],
            ['code' => 'ris-pacs', 'name' => 'RIS / PACS', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Radiology Information System / PACS'],
            ['code' => 'pendaftaran-online', 'name' => 'Pendaftaran Online', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Pendaftaran online / website'],

            // Network
            ['code' => 'internet', 'name' => 'Internet / ISP', 'category' => DowntimeComponentCategory::Network->value, 'description' => 'Koneksi internet / ISP'],
            ['code' => 'network-devices', 'name' => 'Perangkat Jaringan', 'category' => DowntimeComponentCategory::Network->value, 'description' => 'Switch, router, access point'],
            ['code' => 'firewall', 'name' => 'Firewall', 'category' => DowntimeComponentCategory::Network->value, 'description' => 'Perangkat firewall / keamanan jaringan'],

            // Utility
            ['code' => 'electricity', 'name' => 'Listrik PLN', 'category' => DowntimeComponentCategory::Utility->value, 'description' => 'Sumber listrik utama PLN'],
            ['code' => 'genset', 'name' => 'Genset', 'category' => DowntimeComponentCategory::Utility->value, 'description' => 'Generator cadangan'],
            ['code' => 'ups', 'name' => 'UPS', 'category' => DowntimeComponentCategory::Utility->value, 'description' => 'Uninterruptible Power Supply'],
            ['code' => 'cooling', 'name' => 'Pendingin Ruang Server', 'category' => DowntimeComponentCategory::Utility->value, 'description' => 'AC / precision cooling ruang server'],

            // Infrastructure
            ['code' => 'server-infra', 'name' => 'Server & Storage', 'category' => DowntimeComponentCategory::Infrastructure->value, 'description' => 'Server dan storage'],
            ['code' => 'database', 'name' => 'Database Server', 'category' => DowntimeComponentCategory::Infrastructure->value, 'description' => 'Server basis data'],

            // Equipment
            ['code' => 'client-pc', 'name' => 'Komputer Client', 'category' => DowntimeComponentCategory::Equipment->value, 'description' => 'PC / workstation unit'],
            ['code' => 'printer', 'name' => 'Printer', 'category' => DowntimeComponentCategory::Equipment->value, 'description' => 'Printer struk / barcode / gelang'],

            // Operational service
            ['code' => 'svc-pendaftaran', 'name' => 'Layanan Pendaftaran', 'category' => DowntimeComponentCategory::OperationalService->value, 'description' => 'Layanan pendaftaran pasien'],
            ['code' => 'svc-farmasi', 'name' => 'Layanan Farmasi', 'category' => DowntimeComponentCategory::OperationalService->value, 'description' => 'Layanan farmasi / apotek'],
        ];
    }

    /**
     * Peta dependency default: [source_code, affected_code]. Saran affected
     * hanya satu tingkat langsung (tidak rekursif).
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dependencies(): array
    {
        return [
            ['electricity', 'ups'],
            ['electricity', 'cooling'],
            ['electricity', 'network-devices'],
            ['electricity', 'server-infra'],
            ['electricity', 'client-pc'],
            ['cooling', 'server-infra'],
            ['internet', 'bridging-bpjs'],
            ['internet', 'satusehat'],
            ['internet', 'pendaftaran-online'],
            ['internet', 'antrean'],
            ['firewall', 'bridging-bpjs'],
            ['firewall', 'satusehat'],
            ['firewall', 'pendaftaran-online'],
            ['network-devices', 'simrs'],
            ['network-devices', 'rme'],
            ['network-devices', 'sim-farmasi'],
            ['network-devices', 'lis'],
            ['network-devices', 'ris-pacs'],
            ['network-devices', 'antrean'],
            ['server-infra', 'simrs'],
            ['server-infra', 'rme'],
            ['server-infra', 'sim-farmasi'],
            ['server-infra', 'lis'],
            ['server-infra', 'ris-pacs'],
            ['database', 'simrs'],
            ['database', 'rme'],
            ['simrs', 'svc-pendaftaran'],
            ['simrs', 'svc-farmasi'],
            ['sim-farmasi', 'svc-farmasi'],
            ['antrean', 'svc-pendaftaran'],
        ];
    }

    public function run(): void
    {
        DB::transaction(function () {
            $this->syncLocations();
            $this->syncComponents();
            $this->syncDependencies();
        });
    }

    private function syncLocations(): void
    {
        $desired = self::locations();
        $desiredCodes = array_column($desired, 'code');

        foreach ($desired as $location) {
            DowntimeLocation::updateOrCreate(
                ['code' => $location['code']],
                [
                    'name' => $location['name'],
                    'description' => $location['description'],
                    'is_active' => true,
                ]
            );
        }

        DowntimeLocation::whereNotIn('code', $desiredCodes)
            ->get()
            ->each(function (DowntimeLocation $location) {
                if (! $location->isReferenced()) {
                    $location->delete();
                }
            });
    }

    private function syncComponents(): void
    {
        $desired = self::components();
        $desiredCodes = array_column($desired, 'code');

        foreach ($desired as $component) {
            DowntimeComponent::updateOrCreate(
                ['code' => $component['code']],
                [
                    'name' => $component['name'],
                    'category' => $component['category'],
                    'description' => $component['description'],
                    'is_active' => true,
                ]
            );
        }

        DowntimeComponent::whereNotIn('code', $desiredCodes)
            ->get()
            ->each(function (DowntimeComponent $component) {
                if (! $component->isReferenced()) {
                    $component->delete();
                }
            });
    }

    private function syncDependencies(): void
    {
        $ids = DowntimeComponent::pluck('id', 'code');
        $now = now();

        DB::table('downtime_component_dependencies')->delete();

        $rows = [];
        foreach (self::dependencies() as [$source, $affected]) {
            if (! isset($ids[$source], $ids[$affected])) {
                continue;
            }

            $rows[] = [
                'source_component_id' => $ids[$source],
                'affected_component_id' => $ids[$affected],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('downtime_component_dependencies')->insertOrIgnore($rows);
        }
    }
}
