<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_restore_tests', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->date('test_date');
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->string('application_system', 200);
            $table->string('restore_type', 50);
            $table->dateTime('backup_datetime')->nullable();
            $table->string('backup_source', 500)->nullable();
            $table->string('test_environment', 200);
            $table->string('result', 50);
            $table->text('notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['test_date', 'result']);
            $table->index('restore_type');
        });

        Schema::create('server_room_visitors', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->dateTime('entry_at');
            $table->dateTime('exit_at')->nullable();
            $table->string('visitor_name', 200);
            $table->string('unit_or_vendor', 200);
            $table->string('purpose', 500);
            $table->foreignId('escorted_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('inside');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['status', 'entry_at']);
        });

        Schema::create('server_room_inspections', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->date('inspection_date');
            $table->foreignId('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->string('inspection_type', 50);
            $table->json('checklist_items');
            $table->string('conclusion', 50);
            $table->text('follow_up')->nullable();
            $table->string('escalation', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['inspection_date', 'inspection_type']);
            $table->index('conclusion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_room_inspections');
        Schema::dropIfExists('server_room_visitors');
        Schema::dropIfExists('backup_restore_tests');
    }
};
