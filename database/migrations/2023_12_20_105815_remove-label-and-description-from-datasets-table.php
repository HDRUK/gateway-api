<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropColumn([
                'label',
                'short_description',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->char('label', 255)->nullable(false);
            $table->char('short_description', 255)->nullable(false);
        });
    }
};
