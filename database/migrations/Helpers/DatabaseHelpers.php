<?php

namespace Database\Helpers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseHelpers
{
    public static function updateForeignKeysWithCascade(string $tableName, array $foreignKeys)
    {
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

    public static function removeCascadeFromForeignKeys(string $tableName, array $foreignKeys)
    {
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
