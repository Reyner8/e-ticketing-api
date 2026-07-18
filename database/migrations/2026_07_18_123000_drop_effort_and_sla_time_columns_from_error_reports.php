<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('error_reports', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_effort',
                'actual_effort',
                'sla_time_elapsed',
                'sla_time_remaining',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('error_reports', function (Blueprint $table) {
            $table->decimal('estimated_effort', 10, 2)->nullable()->after('completion_date');
            $table->decimal('actual_effort', 10, 2)->nullable()->after('estimated_effort');
            $table->decimal('sla_time_elapsed', 10, 2)->nullable()->after('actual_effort');
            $table->decimal('sla_time_remaining', 10, 2)->nullable()->after('sla_time_elapsed');
        });
    }
};
