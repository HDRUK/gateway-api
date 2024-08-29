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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('authorisation_codes', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('cohort_requests', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('collection_has_durs', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('collection_has_publications', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('collection_has_tools', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dar_applications', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dar_integrations', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dar_sections', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dar_templates', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('data_provider_colls', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dataset_version_has_named_entities', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dataset_version_has_spatial_coverage', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dataset_version_has_tool', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('datasets', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dur', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dur_has_dataset_version', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dur_has_keywords', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dur_has_publications', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('dur_has_tools', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('enquiry_thread_has_dataset_version', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('federations', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('filters', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('libraries', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('named_entities', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('publication_has_dataset_version', function (Blueprint $table) {
            $table->index('deleted_at');
            $table->index('link_type');
        });

        Schema::table('publication_has_tools', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->index('deleted_at');
            $table->index('status');
        });

        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->index('deleted_at');
            $table->index('team_id');
        });

        Schema::table('question_bank_versions', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('tool_has_programming_language', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('tool_has_programming_package', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('tool_has_tags', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('tool_has_type_category', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_deleted_at_index');
        });

        Schema::table('authorisation_codes', function (Blueprint $table) {
            $table->dropIndex('authorisation_codes_deleted_at_index');
        });

        Schema::table('cohort_requests', function (Blueprint $table) {
            $table->dropIndex('cohort_requests_deleted_at_index');
        });

        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->dropIndex('collection_has_dataset_version_deleted_at_index');
        });

        Schema::table('collection_has_durs', function (Blueprint $table) {
            $table->dropIndex('collection_has_durs_deleted_at_index');
        });

        Schema::table('collection_has_publications', function (Blueprint $table) {
            $table->dropIndex('collection_has_publications_deleted_at_index');
        });

        Schema::table('collection_has_tools', function (Blueprint $table) {
            $table->dropIndex('collection_has_tools_deleted_at_index');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex('collections_deleted_at_index');
        });

        Schema::table('dar_applications', function (Blueprint $table) {
            $table->dropIndex('dar_applications_deleted_at_index');
        });

        Schema::table('dar_integrations', function (Blueprint $table) {
            $table->dropIndex('dar_integrations_deleted_at_index');
        });

        Schema::table('dar_sections', function (Blueprint $table) {
            $table->dropIndex('dar_sections_deleted_at_index');
        });

        Schema::table('dar_templates', function (Blueprint $table) {
            $table->dropIndex('dar_templates_deleted_at_index');
        });

        Schema::table('data_provider_colls', function (Blueprint $table) {
            $table->dropIndex('data_provider_colls_deleted_at_index');
        });

        Schema::table('dataset_version_has_named_entities', function (Blueprint $table) {
            $table->dropIndex('dataset_version_has_named_entities_deleted_at_index');
        });

        Schema::table('dataset_version_has_spatial_coverage', function (Blueprint $table) {
            $table->dropIndex('dataset_version_has_spatial_coverage_deleted_at_index');
        });

        Schema::table('dataset_version_has_tool', function (Blueprint $table) {
            $table->dropIndex('dataset_version_has_tool_deleted_at_index');
        });

        Schema::table('datasets', function (Blueprint $table) {
            $table->dropIndex('datasets_deleted_at_index');
        });

        Schema::table('dur', function (Blueprint $table) {
            $table->dropIndex('dur_deleted_at_index');
        });

        Schema::table('dur_has_dataset_version', function (Blueprint $table) {
            $table->dropIndex('dur_has_dataset_version_deleted_at_index');
        });

        Schema::table('dur_has_keywords', function (Blueprint $table) {
            $table->dropIndex('dur_has_keywords_deleted_at_index');
        });

        Schema::table('dur_has_publications', function (Blueprint $table) {
            $table->dropIndex('dur_has_publications_deleted_at_index');
        });

        Schema::table('dur_has_tools', function (Blueprint $table) {
            $table->dropIndex('dur_has_tools_deleted_at_index');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropIndex('email_templates_deleted_at_index');
        });

        Schema::table('enquiry_thread_has_dataset_version', function (Blueprint $table) {
            $table->dropIndex('enquiry_thread_has_dataset_version_deleted_at_index');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropIndex('features_deleted_at_index');
        });

        Schema::table('federations', function (Blueprint $table) {
            $table->dropIndex('federations_deleted_at_index');
        });

        Schema::table('filters', function (Blueprint $table) {
            $table->dropIndex('filters_deleted_at_index');
        });

        Schema::table('libraries', function (Blueprint $table) {
            $table->dropIndex('libraries_deleted_at_index');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropIndex('licenses_deleted_at_index');
        });

        Schema::table('named_entities', function (Blueprint $table) {
            $table->dropIndex('named_entities_deleted_at_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_deleted_at_index');
        });

        Schema::table('publication_has_dataset_version', function (Blueprint $table) {
            $table->dropIndex('publication_has_dataset_version_deleted_at_index');
            $table->dropIndex('publication_has_dataset_version_link_type_index');
        });

        Schema::table('publication_has_tools', function (Blueprint $table) {
            $table->dropIndex('publication_has_tools_deleted_at_index');
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->dropIndex('publications_deleted_at_index');
            $table->dropIndex('publications_status_index');
        });

        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->dropIndex('question_bank_questions_deleted_at_index');
            $table->dropIndex('question_bank_questions_team_id_index');
        });

        Schema::table('question_bank_versions', function (Blueprint $table) {
            $table->dropIndex('question_bank_versions_deleted_at_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_deleted_at_index');
        });

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropIndex('saved_searches_deleted_at_index');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('tags_deleted_at_index');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('teams_deleted_at_index');
        });

        Schema::table('tool_has_programming_language', function (Blueprint $table) {
            $table->dropIndex('tool_has_programming_language_deleted_at_index');
        });

        Schema::table('tool_has_programming_package', function (Blueprint $table) {
            $table->dropIndex('tool_has_programming_package_deleted_at_index');
        });

        Schema::table('tool_has_tags', function (Blueprint $table) {
            $table->dropIndex('tool_has_tags_deleted_at_index');
        });

        Schema::table('tool_has_type_category', function (Blueprint $table) {
            $table->dropIndex('tool_has_type_category_deleted_at_index');
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->dropIndex('tools_deleted_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_deleted_at_index');
        });
    }
};
