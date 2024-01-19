<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_use_registers', function (Blueprint $table) {
            $table->json('non_gateway_datasets')->nullable(); // nonGatewayDatasets
            $table->json('non_gateway_applicants')->nullable(); // gatewayApplicants
            $table->json('funders_and_sponsors')->nullable();
            $table->json('other_approval_committees')->nullable();
            $table->json('gateway_outputs_tools')->nullable();
            $table->json('gateway_outputs_papers')->nullable();
            $table->json('non_gateway_outputs')->nullable();
            $table->string('project_title')->nullable();
            $table->string('project_id_text')->nullable();
            $table->string('organisation_name')->nullable();
            $table->string('organisation_sector')->nullable();
            $table->text('lay_summary')->nullable();
            $table->text('technical_summary')->nullable();
            $table->timestamp('latest_approval_date')->nullable(); // ???? vs updated_at ??? status
            $table->boolean('manual_upload')->default(1);

            $table->string('rejection_reason')->nullable();
            $table->string('sublicence_arrangements')->nullable();
            $table->text('public_benefit_statement')->nullable();
            $table->string('data_sensitivity_level')->nullable();

            $table->timestamp('project_start_date')->nullable(); // projectStartDate
            $table->timestamp('project_end_date')->nullable(); // projectEndDate

            $table->timestamp('access_date')->nullable(); // accessDate - seems like is a relation with counter

            $table->string('accredited_researcher_status')->nullable(); // accreditedResearcherStatus
            $table->string('confidential_description')->nullable(); // confidentialDataDescription
            $table->string('dataset_linkage_description')->nullable(); // datasetLinkageDescription
            $table->string('duty_of_confidentiality')->nullable(); // dutyOfConfidentiality

            $table->text('legal_basis_for_data_article6')->nullable(); // legalBasisForDataArticle6
            $table->text('legal_basis_for_data_article9')->nullable(); // legalBasisForDataArticle9

            $table->string('national_data_optout')->nullable(); // nationalDataOptOut
            $table->string('organisation_id')->nullable(); // organisationId
            $table->text('privacy_enhancements')->nullable(); // privacyEnhancements
            $table->string('request_category_type')->nullable(); // requestCategoryType
            $table->string('request_frequency')->nullable(); // requestFrequency
            $table->string('access_type')->nullable(); // accessType
            $table->char('mongo_object_dar_id', 24)->nullable(); // projectId which is data_requests._id (mongo)

            $table->text('technicalSummary')->nullable(); // technicalSummary

            $table->bigInteger('user_id')->nullable()->default(null)->unsigned();
            $table->bigInteger('team_id')->nullable()->default(null)->unsigned();
            $table->integer('counter')->default(0); // counter

            $table->char('mongo_object_id', 24)->nullable();
            $table->char('mongo_id', 255)->nullable();
            $table->boolean('enabled')->default(1);

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('team_id')->references('id')->on('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('data_use_registers')) {
            Schema::table('data_use_registers', function (Blueprint $table) {
                $table->dropColumn([
                    'counter',
                    'project_title',
                    'lay_summary',
                    'public_benefit_statement',
                ]);
            });
        }
    }
    // support@boulies.com
};
