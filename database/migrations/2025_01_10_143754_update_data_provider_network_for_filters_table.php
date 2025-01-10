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
        Schema::table('filters', function (Blueprint $table) {
            DB::statement("ALTER TABLE `filters` CHANGE `type` `type` VARCHAR(255) NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `filters` CHANGE `type` `type` ENUM('dataset','collection','tool','course','project','paper','dataUseRegister','dataProvider') NOT NULL");
    }
};
