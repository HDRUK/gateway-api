<?php

use Illuminate\Database\Migrations\Migration;
use Database\Migrations\Traits\HelperFunctions;

return new class () extends Migration {
    use HelperFunctions;

    public function up(): void
    {
        // These tables were previously missed
        $this->updateForeignKeysWithCascade('dur_has_dataset_version', [
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
        ]);

        $this->updateForeignKeysWithCascade('dur_has_keywords', [
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'keyword_id' => ['references' => 'id', 'on' => 'keywords'],
        ]);

        $this->updateForeignKeysWithCascade('dur_has_tools', [
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
        ]);

        $this->updateForeignKeysWithCascade('dur_has_publications', [
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);


        $this->updateForeignKeysWithCascade('collection_has_users', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
        ]);

        $this->updateForeignKeysWithCascade('collection_has_keywords', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'keyword_id' => ['references' => 'id', 'on' => 'keywords'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->removeCascadeFromForeignKeys('dur_has_dataset_version', [
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'dataset_version_id' => ['references' => 'id', 'on' => 'dataset_versions'],
        ]);

        $this->removeCascadeFromForeignKeys('dur_has_keywords', [
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'keyword_id' => ['references' => 'id', 'on' => 'keywords'],
        ]);

        $this->removeCascadeFromForeignKeys('dur_has_tools', [
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'tool_id' => ['references' => 'id', 'on' => 'tools'],
        ]);

        $this->removeCascadeFromForeignKeys('dur_has_publications', [
            'dur_id' => ['references' => 'id', 'on' => 'dur'],
            'publication_id' => ['references' => 'id', 'on' => 'publications'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
            'application_id' => ['references' => 'id', 'on' => 'applications'],
        ]);


        $this->removeCascadeFromForeignKeys('collection_has_users', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'user_id' => ['references' => 'id', 'on' => 'users'],
        ]);

        $this->removeCascadeFromForeignKeys('collection_has_keywords', [
            'collection_id' => ['references' => 'id', 'on' => 'collections'],
            'keyword_id' => ['references' => 'id', 'on' => 'keywords'],
        ]);
    }


};
