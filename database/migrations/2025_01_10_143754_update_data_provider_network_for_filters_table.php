<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `filters` CHANGE `type` `type` VARCHAR2(255) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `filters` CHANGE `type` `type` ENUM('dataset','collection','tool','course','project','paper','dataUseRegister','dataProvider') NOT NULL");
    }
};
