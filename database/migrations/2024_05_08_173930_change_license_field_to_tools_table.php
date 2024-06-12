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
        DB::table('tools')->update(['license' => null]);

        Schema::table('tools', function (Blueprint $table) {
            $table->bigInteger('license')->unsigned()->nullable()->change();

            $table->foreign('license')->references('id')->on('licenses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['license']);
        });

        // Ensure no big integers exist that could violate the char(45) constraint
        DB::table('tools')->update(['license' => null]);

        Schema::table('tools', function (Blueprint $table) {
            // Change the column type back to char(45)
            $table->char('license', 45)->nullable()->change();
        });
    }
};
