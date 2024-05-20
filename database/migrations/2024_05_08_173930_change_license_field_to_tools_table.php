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
            $table->dropForeign(['license']);
            $table->char('license', 45)->nullable()->change();
        });
    }
};
