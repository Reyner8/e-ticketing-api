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
        Schema::create('feature_request_tags', function (Blueprint $table) {
            $table->string('feature_request_id');
            $table->foreign('feature_request_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->primary(['feature_request_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_request_tags');
    }
};
