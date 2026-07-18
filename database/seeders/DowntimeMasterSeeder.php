<?php

namespace Database\Seeders;

use App\Enums\DowntimeComponentCategory;
use App\Models\DowntimeComponent;
use App\Models\DowntimeLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master data downtime sesuai lingkungan aplikasi nyata.
 *
 * Aman dijalankan ulang (idempotent):
 * - Lokasi & komponen di-upsert berdasarkan `code`.
 * - Master lama di luar daftar ini dihapus (termasuk yang pernah dipakai
 *   riwayat: tautan record_components dilepas dulu supaya FK tidak gagal).
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
            ['code' => 'pendaftaran-rekam-medis', 'name' => 'Pendaftaran / Rekam Medis', 'description' => 'Unit pendaftaran dan rekam medis'],
            ['code' => 'farmasi-rawat-jalan', 'name' => 'Farmasi Rawat Jalan', 'description' => 'Pelayanan farmasi rawat jalan'],
            ['code' => 'farmasi-rawat-inap', 'name' => 'Farmasi Rawat Inap', 'description' => 'Pelayanan farmasi rawat inap'],
            ['code' => 'ugd', 'name' => 'UGD', 'description' => 'Unit Gawat Darurat'],
            ['code' => 'radiologi', 'name' => 'Radiologi', 'description' => 'Instalasi radiologi'],
            ['code' => 'laboratorium', 'name' => 'Laboratorium', 'description' => 'Laboratorium klinik'],
            ['code' => 'kasir', 'name' => 'Kasir', 'description' => 'Unit pelayanan kasir'],
            ['code' => 'upi', 'name' => 'UPI', 'description' => 'Unit Perawatan Intensif'],
            ['code' => 'ok', 'name' => 'OK', 'description' => 'Kamar operasi'],
            ['code' => 'rawat-inap-carolus', 'name' => 'Rawat Inap Carolus', 'description' => 'Unit rawat inap Carolus'],
            ['code' => 'rawat-inap-lukas', 'name' => 'Rawat Inap Lukas', 'description' => 'Unit rawat inap Lukas'],
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
            ['code' => 'antrean', 'name' => 'Antrean', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Aplikasi antrean pasien (online / Mobile JKN)'],
            ['code' => 'bridging-bpjs', 'name' => 'Bridging BPJS', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Integrasi BPJS (VClaim / Antrean / Aplicares) - modul SIMRS'],
            ['code' => 'bridging-satusehat', 'name' => 'Bridging SATUSEHAT', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Integrasi SATUSEHAT - modul SIMRS'],

            // Network
            ['code' => 'internet', 'name' => 'Internet / ISP', 'category' => DowntimeComponentCategory::Network->value, 'description' => 'Koneksi internet / ISP'],
            ['code' => 'network-devices', 'name' => 'Perangkat Jaringan', 'category' => DowntimeComponentCategory::Network->value, 'description' => 'Switch, router, access point'],

            // Infrastructure
            ['code' => 'server-simrs', 'name' => 'Server SIMRS', 'category' => DowntimeComponentCategory::Infrastructure->value, 'description' => 'Server aplikasi & database SIMRS'],
            ['code' => 'server-antrean', 'name' => 'Server Antrean', 'category' => DowntimeComponentCategory::Infrastructure->value, 'description' => 'Server aplikasi & database Antrean'],

            // Utility
            ['code' => 'electricity', 'name' => 'Listrik PLN', 'category' => DowntimeComponentCategory::Utility->value, 'description' => 'Sumber listrik utama PLN'],
            ['code' => 'ups', 'name' => 'UPS', 'category' => DowntimeComponentCategory::Utility->value, 'description' => 'Uninterruptible Power Supply'],
            ['code' => 'genset', 'name' => 'Genset', 'category' => DowntimeComponentCategory::Utility->value, 'description' => 'Generator cadangan'],
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
            // Listrik → perangkat jaringan & server
            ['electricity', 'network-devices'],
            ['electricity', 'server-simrs'],
            ['electricity', 'server-antrean'],

            // Jaringan → aplikasi client-server
            ['network-devices', 'simrs'],
            ['network-devices', 'antrean'],

            // Server → aplikasi di atasnya
            ['server-simrs', 'simrs'],
            ['server-antrean', 'antrean'],

            // Internet → integrasi keluar & antrean online
            ['internet', 'bridging-bpjs'],
            ['internet', 'bridging-satusehat'],
            ['internet', 'antrean'],

            // SIMRS → bridging (modul di dalam SIMRS)
            ['simrs', 'bridging-bpjs'],
            ['simrs', 'bridging-satusehat'],
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
                // Lepas referensi dari record agar lokasi lama bisa dihapus.
                if (Schema::hasTable('downtime_record_locations')) {
                    DB::table('downtime_record_locations')
                        ->where('location_id', $location->id)
                        ->delete();
                }
                if (Schema::hasColumn('downtime_records', 'location_id')) {
                    DB::table('downtime_records')
                        ->where('location_id', $location->id)
                        ->update(['location_id' => null]);
                }
                $location->delete();
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
                // Lepas tautan riwayat + dependency, lalu hapus master lama.
                DB::table('downtime_record_components')
                    ->where('component_id', $component->id)
                    ->delete();
                DB::table('downtime_component_dependencies')
                    ->where('source_component_id', $component->id)
                    ->orWhere('affected_component_id', $component->id)
                    ->delete();
                $component->delete();
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
