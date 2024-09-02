<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function updateForeignKeysWithCascade(string $tableName, array $foreignKeys)
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

function removeCascadeFromForeignKeys(string $tableName, array $foreignKeys)
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


return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For tool_has_tags table
        updateForeignKeysWithCascade('tool_has_tags', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'tag_id' => ['references' => 'id', 'on' => 'tags'],
        ]);

        // For tool_has_type_category table
        updateForeignKeysWithCascade('tool_has_type_category', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'type_category_id' => ['references' => 'id', 'on' => 'type_categories'],
        ]);

        updateForeignKeysWithCascade('tool_has_programming_language', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_language_id' => ['references' => 'id', 'on' => 'programming_languages'],
        ]);

        updateForeignKeysWithCascade('tool_has_programming_package', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_package_id' => ['references' => 'id', 'on' => 'programming_packages'],
        ]);

        updateForeignKeysWithCascade('publication_has_tools', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        updateForeignKeysWithCascade('publication_has_dataset_version', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
        ]);

        updateForeignKeysWithCascade('collection_has_durs', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        updateForeignKeysWithCascade('collection_has_datasets', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dataset_id' => ['references' => 'id', 'on' => 'datasets'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        updateForeignKeysWithCascade('collection_has_publications', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        updateForeignKeysWithCascade('collection_has_tools', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For tool_has_tags table
        removeCascadeFromForeignKeys('tool_has_tags', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'tag_id' => ['references' => 'id', 'on' => 'tags'],
        ]);

        // For tool_has_type_category table
        removeCascadeFromForeignKeys('tool_has_type_category', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'type_category_id' => ['references' => 'id', 'on' => 'type_categories'],
        ]);

        removeCascadeFromForeignKeys('tool_has_programming_language', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_language_id' => ['references' => 'id', 'on' => 'programming_languages'],
        ]);

        removeCascadeFromForeignKeys('tool_has_programming_package', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_package_id' => ['references' => 'id', 'on' => 'programming_packages'],
        ]);

        removeCascadeFromForeignKeys('publication_has_tools', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        removeCascadeFromForeignKeys('publication_has_dataset_version', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
        ]);

        removeCascadeFromForeignKeys('collection_has_durs', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        removeCascadeFromForeignKeys('collection_has_datasets', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dataset_id' => ['references' => 'id', 'on' => 'datasets'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        removeCascadeFromForeignKeys('collection_has_publications', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        removeCascadeFromForeignKeys('collection_has_tools', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

    }
};
