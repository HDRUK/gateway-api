<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     *
     * 255 (the unqualified default for a migration string() column) is too
     * short for real-world URLs in practice, 2048 should be enough.
     */
    public function up(): void
    {
        Schema::table('dur_outputs', function (Blueprint $table) {
            $table->string('url', 2048)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dur_outputs', function (Blueprint $table) {
            $table->string('url')->nullable()->change();
        });
    }
};
