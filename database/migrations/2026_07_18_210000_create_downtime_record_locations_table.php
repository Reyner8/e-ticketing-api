<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downtime_record_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('downtime_id')
                ->constrained('downtime_records')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('location_id')
                ->constrained('downtime_locations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['downtime_id', 'location_id'], 'downtime_record_location_unique');
        });

        if (Schema::hasColumn('downtime_records', 'location_id')) {
            $now = now();
            $rows = DB::table('downtime_records')
                ->whereNotNull('location_id')
                ->get(['id', 'location_id'])
                ->map(fn ($record) => [
                    'downtime_id' => $record->id,
                    'location_id' => $record->location_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            if ($rows !== []) {
                DB::table('downtime_record_locations')->insertOrIgnore($rows);
            }

            Schema::table('downtime_records', function (Blueprint $table) {
                $table->dropConstrainedForeignId('location_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('downtime_records', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('reported_by')
                ->constrained('downtime_locations')
                ->nullOnDelete();
        });

        DB::table('downtime_record_locations')
            ->orderBy('id')
            ->get()
            ->groupBy('downtime_id')
            ->each(function ($links, $downtimeId) {
                DB::table('downtime_records')
                    ->where('id', $downtimeId)
                    ->update(['location_id' => $links->first()->location_id]);
            });

        Schema::dropIfExists('downtime_record_locations');
    }
};
