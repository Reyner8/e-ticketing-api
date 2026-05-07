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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('loggable_id');
            $table->string('loggable_type');
            $table->string('action', 50);
            $table->text('description');
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->onUpdate('cascade')
                ->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->json('details')->nullable();
            $table->foreignId('target_user_id')
                ->nullable()
                ->constrained('users')
                ->onUpdate('cascade')
                ->nullOnDelete();

            $table->index(['loggable_id', 'loggable_type']);
            $table->index('performed_at');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
