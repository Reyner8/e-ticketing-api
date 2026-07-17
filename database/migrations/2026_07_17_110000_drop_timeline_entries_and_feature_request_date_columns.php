<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('timeline_entries');

        Schema::table('feature_requests', function (Blueprint $table) {
            $table->dropColumn([
                'date_submitted',
                'approval_date',
                'assignment_date',
                'start_date',
                'completion_date',
                'review_date',
                'sla_time_elapsed',
                'sla_time_remaining',
            ]);
        });
    }

    public function down(): void
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
            $table->index('feature_request_id');
            $table->index('phase');
        });

        Schema::table('feature_requests', function (Blueprint $table) {
            $table->timestamp('date_submitted')->useCurrent();
            $table->timestamp('approval_date')->nullable();
            $table->timestamp('assignment_date')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('completion_date')->nullable();
            $table->timestamp('review_date')->nullable();
            $table->decimal('sla_time_elapsed', 10, 2)->nullable();
            $table->decimal('sla_time_remaining', 10, 2)->nullable();
        });
    }
};
