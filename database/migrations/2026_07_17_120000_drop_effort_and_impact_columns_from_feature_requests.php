<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_requests', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_effort',
                'actual_effort',
                'roi_impact',
                'quality_impact',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('feature_requests', function (Blueprint $table) {
            $table->decimal('estimated_effort', 10, 2)->nullable()->after('due_date');
            $table->decimal('actual_effort', 10, 2)->nullable()->after('estimated_effort');
            $table->text('roi_impact')->nullable()->after('rejection_reason');
            $table->text('quality_impact')->nullable()->after('roi_impact');
        });
    }
};
