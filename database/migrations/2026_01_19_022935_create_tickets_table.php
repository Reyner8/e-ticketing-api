<?php

use App\Enums\TicketStatus;
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
            Schema::create('tickets', function (Blueprint $table) {
                  $table->string('id')->primary();
                  $table->string('title', 200);
                  $table->text('description');
                  $table->string('category', 50);
                  $table->string('priority', 50);
                  $table->string('status', 50)->default(TicketStatus::Draft->value);
                  $table->foreignId('reporter_id')
                        ->constrained('users')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
                  $table->foreignId('assigned_to_id')
                        ->nullable()
                        ->constrained('users')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
                  $table->string('assigned_team', 50)->nullable();
                  $table->timestamp('date_reported')->useCurrent();
                  $table->timestamp('due_date')->nullable();
                  $table->timestamp('resolved_date')->nullable();
                  $table->timestamp('closed_date')->nullable();
                  $table->boolean('sla_breached')->default(false);
                  $table->decimal('response_time', 10, 2)->nullable();
                  $table->decimal('resolution_time', 10, 2)->nullable();
                  $table->decimal('estimated_effort', 10, 2)->nullable();
                  $table->decimal('actual_effort', 10, 2)->nullable();
                  $table->string('parent_ticket_id')->nullable();
                  $table->foreign('parent_ticket_id')
                        ->references('id')
                        ->on('tickets')
                        ->nullOnDelete();
                  $table->string('converted_to_type', 50)->nullable();
                  $table->string('converted_to_id')->nullable();
                  $table->timestamp('converted_at')->nullable();
                  $table->foreignId('converted_by')
                        ->nullable()
                        ->constrained('users')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
                  $table->text('conversion_reason')->nullable();
                  $table->timestamps();

                  // Index
                  $table->index('category');
                  $table->index('priority');
                  $table->index('status');
                  $table->index('reporter_id');
                  $table->index('assigned_to_id');
                  $table->index('date_reported');
                  $table->index('sla_breached');
                  $table->index('converted_to_type');
            });
      }

      /**
       * Reverse the migrations.
       */
      public function down(): void
      {
            Schema::dropIfExists('tickets');
      }
};
