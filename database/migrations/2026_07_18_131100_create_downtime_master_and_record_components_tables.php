<?php

use App\Enums\DowntimeComponentCategory;
use App\Enums\DowntimeComponentRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downtime_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
            $table->index('name');
        });

        Schema::create('downtime_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('category', 50);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index('name');
        });

        Schema::create('downtime_component_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_component_id')
                ->constrained('downtime_components')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('affected_component_id')
                ->constrained('downtime_components')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['source_component_id', 'affected_component_id'],
                'downtime_component_dep_unique'
            );
        });

        Schema::table('downtime_records', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('reported_by')
                ->constrained('downtime_locations')
                ->nullOnDelete();
        });

        Schema::create('downtime_record_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('downtime_id')
                ->constrained('downtime_records')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('component_id')
                ->constrained('downtime_components')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('role', 20);
            $table->timestamps();

            $table->unique(['downtime_id', 'component_id', 'role'], 'downtime_record_component_unique');
            $table->index('role');
        });

        $this->seedDefaultsAndBackfill();

        Schema::dropIfExists('downtime_affected_systems');
    }

    public function down(): void
    {
        Schema::create('downtime_affected_systems', function (Blueprint $table) {
            $table->foreignId('downtime_id')
                ->constrained('downtime_records')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('system_name', 200);
            $table->primary(['downtime_id', 'system_name']);
        });

        $legacyRows = DB::table('downtime_record_components')
            ->join('downtime_components', 'downtime_components.id', '=', 'downtime_record_components.component_id')
            ->where('downtime_record_components.role', DowntimeComponentRole::Affected->value)
            ->select([
                'downtime_record_components.downtime_id',
                'downtime_components.name as system_name',
            ])
            ->get();

        foreach ($legacyRows as $row) {
            DB::table('downtime_affected_systems')->insertOrIgnore([
                'downtime_id' => $row->downtime_id,
                'system_name' => $row->system_name,
            ]);
        }

        Schema::dropIfExists('downtime_record_components');

        Schema::table('downtime_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::dropIfExists('downtime_component_dependencies');
        Schema::dropIfExists('downtime_components');
        Schema::dropIfExists('downtime_locations');
    }

    private function seedDefaultsAndBackfill(): void
    {
        $now = now();

        $locations = [
            ['code' => 'main-building', 'name' => 'Gedung Utama', 'description' => 'Lokasi operasional utama'],
            ['code' => 'server-room', 'name' => 'Ruang Server', 'description' => 'Data center / server room'],
            ['code' => 'registration', 'name' => 'Unit Registrasi', 'description' => 'Area pendaftaran pasien'],
            ['code' => 'inpatient', 'name' => 'Rawat Inap', 'description' => 'Unit rawat inap'],
        ];

        foreach ($locations as $location) {
            DB::table('downtime_locations')->insert([
                ...$location,
                'is_active' => true,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $components = [
            ['code' => 'internet', 'name' => 'Internet', 'category' => DowntimeComponentCategory::Network->value, 'description' => 'Koneksi internet / ISP'],
            ['code' => 'electricity', 'name' => 'Electricity Supply', 'category' => DowntimeComponentCategory::Utility->value, 'description' => 'Kelistrikan perusahaan / UPS / genset'],
            ['code' => 'simrs', 'name' => 'SIMRS', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Sistem Informasi Manajemen Rumah Sakit'],
            ['code' => 'antrean', 'name' => 'Queue Application', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Aplikasi antrean'],
            ['code' => 'rme', 'name' => 'RME', 'category' => DowntimeComponentCategory::Application->value, 'description' => 'Rekam Medis Elektronik'],
            ['code' => 'network-devices', 'name' => 'Network Devices', 'category' => DowntimeComponentCategory::Network->value, 'description' => 'Switch, router, access point'],
            ['code' => 'server-infra', 'name' => 'Server Infrastructure', 'category' => DowntimeComponentCategory::Infrastructure->value, 'description' => 'Server dan storage'],
            ['code' => 'ops-service', 'name' => 'Operational Services', 'category' => DowntimeComponentCategory::OperationalService->value, 'description' => 'Layanan operasional unit'],
        ];

        foreach ($components as $component) {
            DB::table('downtime_components')->insert([
                ...$component,
                'is_active' => true,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $componentIds = DB::table('downtime_components')->pluck('id', 'code');

        $dependencies = [
            ['internet', 'simrs'],
            ['internet', 'antrean'],
            ['internet', 'rme'],
            ['electricity', 'network-devices'],
            ['electricity', 'server-infra'],
            ['electricity', 'ops-service'],
            ['network-devices', 'simrs'],
            ['network-devices', 'antrean'],
            ['server-infra', 'simrs'],
            ['server-infra', 'rme'],
        ];

        foreach ($dependencies as [$source, $affected]) {
            if (! isset($componentIds[$source], $componentIds[$affected])) {
                continue;
            }

            DB::table('downtime_component_dependencies')->insert([
                'source_component_id' => $componentIds[$source],
                'affected_component_id' => $componentIds[$affected],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('downtime_affected_systems')) {
            return;
        }

        $legacy = DB::table('downtime_affected_systems')->get();
        $existingByName = DB::table('downtime_components')
            ->get()
            ->keyBy(fn ($row) => Str::lower(trim($row->name)));

        foreach ($legacy as $row) {
            $name = trim((string) $row->system_name);
            if ($name === '') {
                continue;
            }

            $key = Str::lower($name);
            if (! $existingByName->has($key)) {
                $code = Str::slug($name);
                if ($code === '') {
                    $code = 'component-'.Str::lower(Str::random(6));
                }

                $baseCode = $code;
                $suffix = 1;
                while (DB::table('downtime_components')->where('code', $code)->exists()) {
                    $code = $baseCode.'-'.$suffix;
                    $suffix++;
                }

                $id = DB::table('downtime_components')->insertGetId([
                    'code' => $code,
                    'name' => $name,
                    'category' => DowntimeComponentCategory::Other->value,
                    'description' => 'Migrated from legacy affected systems',
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $existingByName[$key] = (object) ['id' => $id, 'name' => $name];
            }

            DB::table('downtime_record_components')->insertOrIgnore([
                'downtime_id' => $row->downtime_id,
                'component_id' => $existingByName[$key]->id,
                'role' => DowntimeComponentRole::Affected->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
