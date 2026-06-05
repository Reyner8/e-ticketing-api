<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('downtime_affected_systems', function (Blueprint $table) {
            $table->foreignId('downtime_id')
                ->constrained('downtime_records')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('system_name', 200);

            $table->primary(['downtime_id', 'system_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downtime_affected_systems');
    }
};
