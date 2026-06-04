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
        Schema::create('conversion_histories', function (Blueprint $table) {
            $table->id();
            $table->string('source_ticket_id');
            $table->foreign('source_ticket_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();
            $table->string('target_type');
            $table->string('target_id');
            $table->foreignId('converted_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('converted_at')->useCurrent();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            // Index
            $table->index('source_ticket_id');
            $table->index('target_type');
            $table->index('converted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_histories');
    }
};
