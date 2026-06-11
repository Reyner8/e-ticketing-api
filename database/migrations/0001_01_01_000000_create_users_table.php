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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password');
            $table->string('role', 50)->nullable();
            $table->string('team', 50)->nullable();
            $table->string('avatar', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('pref_dark_mode')->default(false);
            $table->boolean('pref_email_notifications')->default(true);
            $table->boolean('pref_sla_alerts')->default(true);
            $table->boolean('pref_downtime_alerts')->default(true);
            $table->string('pref_digest_frequency', 50)->default('immediate');
            $table->string('pref_quiet_hours', 100)->nullable()->comment('Format: "22:00-08:00"');

            // Index
            $table->index('email');
            $table->index('role');
            $table->index('team');
            $table->index('is_active');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        
    }
};
