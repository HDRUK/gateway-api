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
        Schema::table('datasets', function (Blueprint $table) {
            $table->index('datasetid');
            $table->index('user_id');
            $table->index('team_id');
            $table->index('status'); // All enums in mysql are encoded as integers
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index('tool_id');
            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('sector_id');
            $table->index('orcid');
            $table->index('mongo_id');
            $table->index('hubspot_id');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index('team_id');
            $table->index('user_id');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->index('role_id');
            $table->index('permission_id');
        });

        Schema::table('team_user_has_roles', function (Blueprint $table) {
            $table->index('team_has_user_id');
            $table->index('role_id');
        });

        Schema::table('team_has_federations', function (Blueprint $table) {
            $table->index('team_id');
            $table->index('federation_id');
        });

        Schema::table('federation_has_notifications', function (Blueprint $table) {
            $table->index('federation_id');
            $table->index('notification_id');
        });

        Schema::table('user_has_roles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('role_id');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->index('mongo_object_id'); // Not sure about how optimal this one is
            $table->index('pid');
        });

        Schema::table('cohort_requests', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('cohort_request_logs', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('cohort_request_has_logs', function (Blueprint $table) {
            $table->index('cohort_request_id');
            $table->index('cohort_request_log_id');
        });

        Schema::table('team_user_has_notifications', function (Blueprint $table) {
            $table->index('team_has_user_id');
            $table->index('notification_id');
        });

        Schema::table('application_has_notifications', function (Blueprint $table) {
            $table->index('application_id');
            $table->index('notification_id');
        });

        Schema::table('cohort_request_has_permissions', function (Blueprint $table) {
            $table->index('cohort_request_id');
            $table->index('permission_id');
        });

        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->index('dataset_id');
            $table->index('version');
            $table->index('provider_team_id');
        });

        Schema::table('collection_has_datasets', function (Blueprint $table) {
            $table->index('collection_id');
            $table->index('dataset_id');
            $table->index('user_id');
            $table->index('application_id');
        });

        Schema::table('collection_has_publications', function (Blueprint $table) {
            $table->index('collection_id');
            $table->index('publication_id');
            $table->index('user_id');
            $table->index('application_id');
        });

        Schema::table('collection_has_keywords', function (Blueprint $table) {
            $table->index('collection_id');
            $table->index('keyword_id');
        });

        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('team_id');
        });

        Schema::table('enquiry_messages', function (Blueprint $table) {
            $table->index('thread_id');
        });

        Schema::table('dur', function (Blueprint $table) {
            $table->index('mongo_object_id');
            $table->index('user_id');
            $table->index('team_id');
            $table->index('application_id');
            $table->index('sector_id');
            $table->index('status');
        });

        Schema::table('dur_has_datasets', function (Blueprint $table) {
            $table->index('dur_id');
            $table->index('dataset_id');
            $table->index('user_id');
            $table->index('application_id');
        });

        Schema::table('dur_has_keywords', function (Blueprint $table) {
            $table->index('dur_id');
            $table->index('keyword_id');
        });

        Schema::table('dur_has_publications', function (Blueprint $table) {
            $table->index('dur_id');
            $table->index('publication_id');
            $table->index('user_id');
            $table->index('application_id');
        });

        Schema::table('dur_has_tools', function (Blueprint $table) {
            $table->index('dur_id');
            $table->index('tool_id');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->index('team_id');
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->index('team_id');
            $table->index('license');
        });

        Schema::table('publication_has_dataset', function (Blueprint $table) {
            $table->index('publication_id');
            $table->index('dataset_id');
            $table->index('link_type');
        });

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('sort_order');
        });

        Schema::table('collection_has_tools', function (Blueprint $table) {
            $table->index('collection_id');
            $table->index('tool_id');
            $table->index('user_id');
            $table->index('application_id');
        });

        Schema::table('collection_has_durs', function (Blueprint $table) {
            $table->index('collection_id');
            $table->index('dur_id');
            $table->index('user_id');
            $table->index('application_id');
        });

        Schema::table('tool_has_programming_language', function (Blueprint $table) {
            $table->index('tool_id');
            $table->index('programming_language_id');
        });

        Schema::table('tool_has_type_category', function (Blueprint $table) {
            $table->index('tool_id');
            $table->index('type_category_id');
        });

        Schema::table('publication_has_tools', function (Blueprint $table) {
            $table->index('publication_id');
            $table->index('tool_id');
            $table->index('user_id');
            $table->index('application_id');
        });

        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->index('section_id');
            $table->index('user_id');
            $table->index('locked');
            $table->index('archived');
        });

        Schema::table('dar_applications', function (Blueprint $table) {
            $table->index('applicant_id');
            $table->index('submission_status');
        });

        Schema::table('dar_templates', function (Blueprint $table) {
            $table->index('team_id');
            $table->index('user_id');
            $table->index('published');
        });

        Schema::table('dar_application_answers', function (Blueprint $table) {
            $table->index('question_id');
            $table->index('application_id');
            $table->index('contributor_id');
        });

        Schema::table('dar_application_has_questions', function (Blueprint $table) {
            $table->index('application_id');
            $table->index('question_id');
        });

        Schema::table('dar_app_q_has_enq_threads', function (Blueprint $table) {
            $table->index('equiry_thread_id');
            $table->index('dar_application_q_id');
        });

        Schema::table('dar_template_has_questions', function (Blueprint $table) {
            $table->index('template_id');
            $table->index('question_id');
        });

        Schema::table('question_bank_versions', function (Blueprint $table) {
            $table->index('question_parent_id');
        });

        Schema::table('uploads', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
        });

        Schema::table('libraries', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('dataset_id');
        });

        Schema::table('dataset_version_has_tool', function (Blueprint $table) {
            $table->index('tool_id');
            $table->index('dataset_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nope!
    }
};
