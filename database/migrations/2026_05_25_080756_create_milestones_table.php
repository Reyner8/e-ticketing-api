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
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->string('feature_request_id');
            $table->foreign('feature_request_id')
                ->references('id')
                ->on('feature_requests')
                ->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->timestamp('target_date');
            $table->timestamp('completed_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->integer('progress')->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // Index
            $table->index('feature_request_id');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
