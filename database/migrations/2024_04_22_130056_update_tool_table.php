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
        Schema::table('tools', function(Blueprint $table)
        {
            $table->dropColumn('programming_language');
        });

        Schema::table('tools', function(Blueprint $table)
        {
            $table->dropColumn('programming_package');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->string('programming_language', 255)->nullable();
            $table->string('programming_package', 255)->nullable();
        });
    }
};
