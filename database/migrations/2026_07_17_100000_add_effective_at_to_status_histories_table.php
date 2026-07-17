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
            $table->timestamp('effective_at')->nullable()->after('changed_at');
        });

        DB::table('status_histories')->whereNull('effective_at')->update([
            'effective_at' => DB::raw('changed_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('status_histories', function (Blueprint $table) {
            $table->dropColumn('effective_at');
        });
    }
};
