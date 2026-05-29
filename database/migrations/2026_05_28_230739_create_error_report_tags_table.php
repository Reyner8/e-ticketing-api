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
        Schema::create('error_report_tags', function (Blueprint $table) {
            $table->string('error_report_id');
            $table->foreign('error_report_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->primary(['error_report_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_report_tags');
    }
};
