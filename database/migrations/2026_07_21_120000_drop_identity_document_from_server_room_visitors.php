<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_room_visitors', function (Blueprint $table) {
            $table->dropColumn('identity_document');
        });
    }

    public function down(): void
    {
        Schema::table('server_room_visitors', function (Blueprint $table) {
            $table->string('identity_document', 200)->default('')->after('unit_or_vendor');
        });
    }
};
