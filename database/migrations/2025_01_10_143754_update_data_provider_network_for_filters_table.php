<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('filters', function (Blueprint $table) {
            $table->string('type_temp', 255)->nullable();
        });

        DB::statement('UPDATE filters SET type_temp = type');

        Schema::table('filters', function (Blueprint $table) {
            $table->dropUnique(['type', 'keys']);
            $table->dropColumn('type');
        });

        Schema::table('filters', function (Blueprint $table) {
            $table->renameColumn('type_temp', 'type');
            $table->unique(['type', 'keys']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filters', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->enum('type', ['dataset','collection','tool','course','project','paper','dataUseRegister','dataProvider'])->nullable();
        });
    }
};
