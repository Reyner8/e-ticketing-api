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
        Schema::create('timeline_entries', function (Blueprint $table) {
            $table->id();
            $table->string('feature_request_id');
            $table->foreign('feature_request_id')
                ->references('id')
                ->on('feature_requests')
                ->cascadeOnDelete();
            $table->string('phase', 50);
            $table->string('title', 200);
            $table->text('description');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->integer('progress')->default(0);
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->text('notes')->nullable();
            
            // Index
            $table->index('feature_request_id');
            $table->index('phase');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline_entries');
    }
};
