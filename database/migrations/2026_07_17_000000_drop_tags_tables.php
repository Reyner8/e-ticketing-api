<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('feature_request_tags');
        Schema::dropIfExists('error_report_tags');
        Schema::dropIfExists('ticket_tags');
        Schema::dropIfExists('tags');
    }

    public function down(): void
    {
        // Tags feature removed — recreate via original migrations if rollback needed.
    }
};
