<?php

use Illuminate\Database\Migrations\Migration;
use Database\Helpers\DatabaseHelpers;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---- Tools ----
        DatabaseHelpers::updateForeignKeysWithCascade('tool_has_tags', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'tag_id' => ['references' => 'id', 'on' => 'tags'],
        ]);

        DatabaseHelpers::updateForeignKeysWithCascade('tool_has_type_category', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'type_category_id' => ['references' => 'id', 'on' => 'type_categories'],
        ]);

        DatabaseHelpers::updateForeignKeysWithCascade('tool_has_programming_language', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_language_id' => ['references' => 'id', 'on' => 'programming_languages'],
        ]);

        DatabaseHelpers::updateForeignKeysWithCascade('tool_has_programming_package', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_package_id' => ['references' => 'id', 'on' => 'programming_packages'],
        ]);

        // ---- Publications ----
        DatabaseHelpers::updateForeignKeysWithCascade('publication_has_tools', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        DatabaseHelpers::updateForeignKeysWithCascade('publication_has_dataset_version', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
        ]);

        // ---- collections ----
        DatabaseHelpers::updateForeignKeysWithCascade('collection_has_durs', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        DatabaseHelpers::updateForeignKeysWithCascade('collection_has_datasets', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dataset_id' => ['references' => 'id', 'on' => 'datasets'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        DatabaseHelpers::updateForeignKeysWithCascade('collection_has_publications', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        DatabaseHelpers::updateForeignKeysWithCascade('collection_has_tools', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        // ---- Datasets ----
        // - all good, these already have onCascade set


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DatabaseHelpers::removeCascadeFromForeignKeys('tool_has_tags', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'tag_id' => ['references' => 'id', 'on' => 'tags'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('tool_has_type_category', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'type_category_id' => ['references' => 'id', 'on' => 'type_categories'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('tool_has_programming_language', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_language_id' => ['references' => 'id', 'on' => 'programming_languages'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('tool_has_programming_package', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_package_id' => ['references' => 'id', 'on' => 'programming_packages'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('publication_has_tools', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('publication_has_dataset_version', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('collection_has_durs', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('collection_has_datasets', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dataset_id' => ['references' => 'id', 'on' => 'datasets'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('collection_has_publications', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        DatabaseHelpers::removeCascadeFromForeignKeys('collection_has_tools', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

    }
};
