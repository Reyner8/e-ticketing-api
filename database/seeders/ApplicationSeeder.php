<?php

namespace Database\Seeders;

use App\Models\Application;
use Illuminate\Database\Seeder;

/**
 * Master aplikasi/sistem bersama (Feature Request, Backup Restore, dll.).
 * Idempotent: upsert by code.
 */
class ApplicationSeeder extends Seeder
{
    /**
     * @return array<int, array{code: string, name: string, description: string, sort_order: int}>
     */
    public static function items(): array
    {
        return [
            ['code' => 'simrs', 'name' => 'SIMRS', 'description' => 'Sistem Informasi Manajemen Rumah Sakit', 'sort_order' => 10],
            ['code' => 'rme', 'name' => 'RME', 'description' => 'Rekam Medis Elektronik', 'sort_order' => 20],
            ['code' => 'antrean', 'name' => 'Antrean', 'description' => 'Aplikasi antrean pasien (online / Mobile JKN)', 'sort_order' => 30],
            ['code' => 'bridging-bpjs', 'name' => 'Bridging BPJS', 'description' => 'Integrasi BPJS (VClaim / Antrean / Aplicares)', 'sort_order' => 40],
            ['code' => 'bridging-satusehat', 'name' => 'Bridging SATUSEHAT', 'description' => 'Integrasi SATUSEHAT', 'sort_order' => 50],
            ['code' => 'lainnya', 'name' => 'Lainnya', 'description' => 'Aplikasi / sistem lain di luar daftar utama', 'sort_order' => 90],
        ];
    }

    public function run(): void
    {
        foreach (self::items() as $item) {
            Application::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
