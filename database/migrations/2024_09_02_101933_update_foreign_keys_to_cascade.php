<?php

use Illuminate\Database\Migrations\Migration;
use Database\Migrations\Traits\HelperFunctions;

return new class () extends Migration {
    use HelperFunctions;

    public function up(): void
    {
        // ---- Tools ----
        $this->updateForeignKeysWithCascade('tool_has_tags', [
             'tool_id' => ['references' => 'id', 'on' => 'tools'],
             'tag_id' => ['references' => 'id', 'on' => 'tags'],
         ]);

        $this->updateForeignKeysWithCascade('tool_has_type_category', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'type_category_id' => ['references' => 'id', 'on' => 'type_categories'],
        ]);

        $this->updateForeignKeysWithCascade('tool_has_programming_language', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_language_id' => ['references' => 'id', 'on' => 'programming_languages'],
        ]);

        $this->updateForeignKeysWithCascade('tool_has_programming_package', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_package_id' => ['references' => 'id', 'on' => 'programming_packages'],
        ]);

        // ---- Publications ----
        $this->updateForeignKeysWithCascade('publication_has_tools', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        $this->updateForeignKeysWithCascade('publication_has_dataset_version', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
        ]);

        // ---- collections ----
        $this->updateForeignKeysWithCascade('collection_has_durs', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        $this->updateForeignKeysWithCascade('collection_has_dataset_version', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        $this->updateForeignKeysWithCascade('collection_has_publications', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        $this->updateForeignKeysWithCascade('collection_has_tools', [
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
        $this->removeCascadeFromForeignKeys('tool_has_tags', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'tag_id' => ['references' => 'id', 'on' => 'tags'],
        ]);

        $this->removeCascadeFromForeignKeys('tool_has_type_category', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'type_category_id' => ['references' => 'id', 'on' => 'type_categories'],
        ]);

        $this->removeCascadeFromForeignKeys('tool_has_programming_language', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_language_id' => ['references' => 'id', 'on' => 'programming_languages'],
        ]);

        $this->removeCascadeFromForeignKeys('tool_has_programming_package', [
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'programming_package_id' => ['references' => 'id', 'on' => 'programming_packages'],
        ]);

        $this->removeCascadeFromForeignKeys('publication_has_tools', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        $this->removeCascadeFromForeignKeys('publication_has_dataset_version', [
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
        ]);

        $this->removeCascadeFromForeignKeys('collection_has_durs', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        $this->removeCascadeFromForeignKeys('collection_has_dataset_version', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        $this->removeCascadeFromForeignKeys('collection_has_publications', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);

        $this->removeCascadeFromForeignKeys('collection_has_tools', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);
    }


};
