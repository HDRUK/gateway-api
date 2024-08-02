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
        Schema::table('collections', function (Blueprint $table) {
            $table->integer('counter')->default(0)->change();
            $table->text('name', 500)->change();
            $table->dropColumn('keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->char('name', 255)->nullable(false)->change();
            $table->char('keywords', 255)->nullable(false);
        });
    }
};
