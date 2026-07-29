<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh installs never had this column (removed from the create migration).
        // Only drop when present so migrate works on SQLite tests and new DBs.
        if (! Schema::hasTable('server_room_visitors')) {
            return;
        }

        if (! Schema::hasColumn('server_room_visitors', 'identity_document')) {
            return;
        }

        Schema::table('server_room_visitors', function (Blueprint $table) {
            $table->dropColumn('identity_document');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('server_room_visitors')) {
            return;
        }

        if (Schema::hasColumn('server_room_visitors', 'identity_document')) {
            return;
        }

        Schema::table('server_room_visitors', function (Blueprint $table) {
            $table->string('identity_document', 200)->default('')->after('unit_or_vendor');
        });
    }
};
