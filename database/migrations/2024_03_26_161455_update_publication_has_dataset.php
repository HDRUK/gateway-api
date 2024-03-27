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
        Schema::table('publication_has_dataset', function (Blueprint $table) {
            $table->enum('link_type', ['ABOUT', 'USING', 'UNKNOWN'])->default('UNKNOWN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_has_dataset', function (Blueprint $table) {
            $table->dropColumn('link_type');
        });
    }
};
