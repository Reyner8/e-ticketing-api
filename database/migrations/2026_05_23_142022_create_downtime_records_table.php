<?php

use App\Enums\DowntimeStatus;
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
        Schema::create('downtime_records', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('type', 50);
            $table->text('reason');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable()->comment('minutes');
            $table->string('impact', 50);
            $table->foreignId('reported_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 50)->default(DowntimeStatus::Ongoing->value);
            $table->text('root_cause')->nullable();
            $table->text('preventive_measures')->nullable();
            $table->integer('affected_users')->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->timestamps();

            //index
            $table->index('type');
            $table->index('status');
            $table->index('start_time');
            $table->index('impact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downtime_records');
    }
};
