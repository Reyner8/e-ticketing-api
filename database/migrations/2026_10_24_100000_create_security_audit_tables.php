<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_audits', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('title', 200);
            $table->string('type', 50);
            $table->date('audit_date');
            $table->string('period', 50)->nullable();
            // Audit adalah bukti rekam — catatan tidak ikut terhapus saat user dihapus.
            $table->foreignId('performed_by')->nullable()
                ->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('external_auditor', 200)->nullable();
            $table->text('scope')->nullable();
            $table->text('summary')->nullable();
            $table->string('status', 50)->default('planned');
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('audit_date');
        });

        Schema::create('security_audit_findings', function (Blueprint $table) {
            $table->id();
            $table->string('security_audit_id', 20);
            $table->foreign('security_audit_id')
                ->references('id')->on('security_audits')->cascadeOnDelete();
            $table->text('description');
            $table->string('severity', 50);
            $table->text('recommendation')->nullable();
            $table->text('corrective_action')->nullable();
            $table->string('status', 50)->default('open');
            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['security_audit_id', 'status']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_findings');
        Schema::dropIfExists('security_audits');
    }
};
