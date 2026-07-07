<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_public_submission')->default(false)->after('sla_breached');
            $table->string('submitter_name', 150)->nullable()->after('is_public_submission');
            $table->string('submitter_email', 150)->nullable()->after('submitter_name');
            $table->string('submitter_phone', 50)->nullable()->after('submitter_email');
            $table->string('submitter_unit', 100)->nullable()->after('submitter_phone');

            $table->index('is_public_submission');
            $table->index('submitter_email');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['is_public_submission']);
            $table->dropIndex(['submitter_email']);
            $table->dropColumn([
                'is_public_submission',
                'submitter_name',
                'submitter_email',
                'submitter_phone',
                'submitter_unit',
            ]);
        });
    }
};
