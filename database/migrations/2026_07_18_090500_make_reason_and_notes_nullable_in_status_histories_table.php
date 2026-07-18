<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_histories', function (Blueprint $table) {
            $table->text('reason')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('status_histories')
            ->whereNull('reason')
            ->update(['reason' => '']);

        DB::table('status_histories')
            ->whereNull('notes')
            ->update(['notes' => '']);

        Schema::table('status_histories', function (Blueprint $table) {
            $table->text('reason')->nullable(false)->change();
            $table->text('notes')->nullable(false)->change();
        });
    }
};
