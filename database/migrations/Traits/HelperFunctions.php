<?php

namespace Database\Migrations\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

trait HelperFunctions
{
    private function updateForeignKeysWithCascade(string $tableName, array $foreignKeys)
    {
        if (DB::getDriverName() === 'sqlite') {
            //this is because tests start failing with:
            // -  "SQLite doesn't support dropping foreign keys (you would need to re-create the table)."
            return;
        }
        Schema::table($tableName, function (Blueprint $table) use ($foreignKeys) {
            // Drop existing foreign keys to replace them with cascade versions
            foreach ($foreignKeys as $foreignKey => $references) {
                $table->dropForeign([$foreignKey]);
            }

            // Add the foreign keys back with cascading deletes
            foreach ($foreignKeys as $foreignKey => $references) {
                $table->foreign($foreignKey)
                      ->references($references['references'])
                      ->on($references['on'])
                      ->onDelete('cascade');
            }
        });
    }

    private function removeCascadeFromForeignKeys(string $tableName, array $foreignKeys)
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        Schema::table($tableName, function (Blueprint $table) use ($foreignKeys) {
            // Drop existing foreign keys with cascade
            foreach ($foreignKeys as $foreignKey => $references) {
                $table->dropForeign([$foreignKey]);
            }

            // Add the foreign keys back without cascading deletes
            foreach ($foreignKeys as $foreignKey => $references) {
                $table->foreign($foreignKey)
                      ->references($references['references'])
                      ->on($references['on']);
            }
        });

    }
}
