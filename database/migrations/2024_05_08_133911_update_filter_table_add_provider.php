<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('filters', function (Blueprint $table) {
            $table->dropUnique(['type', 'keys']);
            $table->renameColumn('type', 'type_old');
        });

        Schema::table('filters', function (Blueprint $table) {
            $table->enum('type', \Config::get('filters.types'));
        });

        DB::statement("UPDATE filters SET type = type_old");

        Schema::table('filters', function (Blueprint $table) {
            $table->dropColumn('type_old');
            $table->unique(['type', 'keys']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        $foreignKeys = $this->listTableForeignKeys('filters');

        if (in_array('filters_type_keys_unique', $foreignKeys)) {
            DB::statement('ALTER TABLE `filters` DROP FOREIGN KEY `filters_type_keys_unique`');
            DB::statement('ALTER TABLE `filters` DROP KEY `filters_type_keys_unique`');
        }

        Schema::table('filters', function (Blueprint $table) {
            $table->dropUnique(['type', 'keys']);
            $table->enum('type_old', [
                'dataset',
                'collection',
                'tool',
                'course',
                'project',
                'paper',
                'dataUseRegister',
            ]);
        });

        DB::statement("UPDATE filters SET type_old = type WHERE filters.type != 'dataProvider'");

        Schema::table('filters', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('filters', function (Blueprint $table) {
            $table->renameColumn('type_old', 'type');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function listTableForeignKeys($table)
    {
        return array_map(function ($key) {
            return $key->getName();
        }, Schema::getForeignKeys($table));
    }
};
