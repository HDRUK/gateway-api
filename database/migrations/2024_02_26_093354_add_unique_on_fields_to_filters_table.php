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

        Schema::enableForeignKeyConstraints();
    }

    public function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        return array_map(function ($key) {
            return $key->getName();
        }, $conn->listTableForeignKeys($table));
    }
};
