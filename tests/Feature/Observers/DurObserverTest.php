<?php

namespace Tests\Feature\Observers;

use Mockery;
use Carbon\Carbon;
use App\Models\Dur;
use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\DatasetVersion;
use App\Observers\DurObserver;
use Tests\Traits\MockExternalApis;
use App\Models\CollectionHasDatasetVersion;

class DurObserverTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $userId;
    protected $teamId;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Collection::flushEventListeners();
        CollectionHasDatasetVersion::flushEventListeners();
        Dur::flushEventListeners();

        $this->observer = Mockery::mock(DurObserver::class)->makePartial();
        app()->instance(DurObserver::class, $this->observer);

        Dur::observe(DurObserver::class);

        $this->userId = User::all()->random()->id;
        $this->teamId = Team::all()->random()->id;
    }

    public function testDurObserverCreatedEventIndexesActiveDur()
    {
        $countInitialDur = Dur::count();
        $this->observer->shouldReceive('indexElasticDur')->once()->with($countInitialDur + 1);

        $userId = User::all()->random()->id;
        $teamId = Team::all()->random()->id;

        $dur = Dur::create([
            'non_gateway_datasets' => ['ICNARC'],
            'non_gateway_applicants' => ['Lila Kilback'],
            'funders_and_sponsors' => ['Newcastle Upon Tyne Hospitals NHS Foundation Trust'],
            'other_approval_committees' => ['REC reference: 14/LO/1965'],
            'gateway_outputs_tools' => [],
            'non_gateway_outputs' => ['https://emj.bmj.com/content/38/9/A2.2'],
            'gateway_outputs_papers' => [],
            'project_title' => 'Birth order and cord blood DNA methylation',
            'project_id_text' => 'B3649',
            'organisation_name' => 'Kings College London',
            'organisation_sector' => 'CQC Registered Health or/and Social Care provider',
            'lay_summary' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'technical_summary' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'latest_approval_date' => Carbon::now(),
            'manual_upload' => 1,
            'rejection_reason' => '',
            'sublicence_arrangements' => '',
            'public_benefit_statement' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'data_sensitivity_level' => '',
            'project_start_date' => Carbon::now(),
            'project_end_date' => Carbon::now(),
            'access_date' => Carbon::now(),
            'accredited_researcher_status' => 'No',
            'confidential_data_description' => '',
            'dataset_linkage_description' => '',
            'duty_of_confidentiality' => 'Not applicable',
            'legal_basis_for_data_article6' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'legal_basis_for_data_article9' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'national_data_optout' => 'No',
            'organisation_id' => 'grid.11201.33',
            'privacy_enhancements' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'request_category_type' => 'Health Services & Delivery',
            'request_frequency' => 'Efficacy & Mechanism Evaluation',
            'access_type' => 'Public Health Research',
            'mongo_object_dar_id' => 'MOBJIDDAR-6556',
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'enabled' => 0,
            'last_activity' => Carbon::now(),
            'counter' => 53816,
            'mongo_object_id' => 'MOBJID-5092',
            'mongo_id' => 86611984357,
            'status' => Dur::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('dur', [
            'id' => $dur->id,
            'project_title' => 'Birth order and cord blood DNA methylation',
            'project_id_text' => 'B3649',
            'organisation_name' => 'Kings College London',
            'status' => Dur::STATUS_ACTIVE,
        ]);
    }

    public function testDurObserverUpdatedEventDeletesActiveDur()
    {
        $countInitialDur = Dur::count();

        $this->observer->shouldReceive('indexElasticDur')->once()->with($countInitialDur + 1);

        $dur = Dur::create([
            'non_gateway_datasets' => ['ICNARC'],
            'non_gateway_applicants' => ['Lila Kilback'],
            'funders_and_sponsors' => ['Newcastle Upon Tyne Hospitals NHS Foundation Trust'],
            'other_approval_committees' => ['REC reference: 14/LO/1965'],
            'gateway_outputs_tools' => [],
            'non_gateway_outputs' => ['https://emj.bmj.com/content/38/9/A2.2'],
            'gateway_outputs_papers' => [],
            'project_title' => 'Birth order and cord blood DNA methylation',
            'project_id_text' => 'B3649',
            'organisation_name' => 'Kings College London',
            'organisation_sector' => 'CQC Registered Health or/and Social Care provider',
            'lay_summary' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'technical_summary' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'latest_approval_date' => Carbon::now(),
            'manual_upload' => 1,
            'rejection_reason' => '',
            'sublicence_arrangements' => '',
            'public_benefit_statement' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'data_sensitivity_level' => '',
            'project_start_date' => Carbon::now(),
            'project_end_date' => Carbon::now(),
            'access_date' => Carbon::now(),
            'accredited_researcher_status' => 'No',
            'confidential_data_description' => '',
            'dataset_linkage_description' => '',
            'duty_of_confidentiality' => 'Not applicable',
            'legal_basis_for_data_article6' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'legal_basis_for_data_article9' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'national_data_optout' => 'No',
            'organisation_id' => 'grid.11201.33',
            'privacy_enhancements' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'request_category_type' => 'Health Services & Delivery',
            'request_frequency' => 'Efficacy & Mechanism Evaluation',
            'access_type' => 'Public Health Research',
            'mongo_object_dar_id' => 'MOBJIDDAR-6556',
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'enabled' => 0,
            'last_activity' => Carbon::now(),
            'counter' => 53816,
            'mongo_object_id' => 'MOBJID-5092',
            'mongo_id' => 86611984357,
            'status' => Dur::STATUS_ACTIVE,
        ]);

        
        $this->observer->shouldReceive('deleteDurFromElastic')->once()->with($countInitialDur + 1);

        $dur->update(['status' => Dur::STATUS_ARCHIVED]);

        $this->assertDatabaseHas('dur', [
            'id' => $dur->id,
            'status' => Dur::STATUS_ARCHIVED,
        ]);
    }

    public function testDurObserverUpdatedEventIndexesWhenStatusBecomesActive()
    {
        $countInitialDur = Dur::count();

        $dur = Dur::create([
            'non_gateway_datasets' => ['ICNARC'],
            'non_gateway_applicants' => ['Lila Kilback'],
            'funders_and_sponsors' => ['Newcastle Upon Tyne Hospitals NHS Foundation Trust'],
            'other_approval_committees' => ['REC reference: 14/LO/1965'],
            'gateway_outputs_tools' => [],
            'non_gateway_outputs' => ['https://emj.bmj.com/content/38/9/A2.2'],
            'gateway_outputs_papers' => [],
            'project_title' => 'Birth order and cord blood DNA methylation',
            'project_id_text' => 'B3649',
            'organisation_name' => 'Kings College London',
            'organisation_sector' => 'CQC Registered Health or/and Social Care provider',
            'lay_summary' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'technical_summary' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'latest_approval_date' => Carbon::now(),
            'manual_upload' => 1,
            'rejection_reason' => '',
            'sublicence_arrangements' => '',
            'public_benefit_statement' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'data_sensitivity_level' => '',
            'project_start_date' => Carbon::now(),
            'project_end_date' => Carbon::now(),
            'access_date' => Carbon::now(),
            'accredited_researcher_status' => 'No',
            'confidential_data_description' => '',
            'dataset_linkage_description' => '',
            'duty_of_confidentiality' => 'Not applicable',
            'legal_basis_for_data_article6' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'legal_basis_for_data_article9' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'national_data_optout' => 'No',
            'organisation_id' => 'grid.11201.33',
            'privacy_enhancements' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'request_category_type' => 'Health Services & Delivery',
            'request_frequency' => 'Efficacy & Mechanism Evaluation',
            'access_type' => 'Public Health Research',
            'mongo_object_dar_id' => 'MOBJIDDAR-6556',
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'enabled' => 0,
            'last_activity' => Carbon::now(),
            'counter' => 53816,
            'mongo_object_id' => 'MOBJID-5092',
            'mongo_id' => 86611984357,
            'status' => Dur::STATUS_DRAFT,
        ]);

        $this->observer->shouldReceive('indexElasticDur')->once()->with($countInitialDur + 1);

        $dur->update(['status' => Dur::STATUS_ACTIVE]);

        $this->assertDatabaseHas('dur', [
            'id' => $dur->id,
            'status' => Dur::STATUS_ACTIVE
        ]);
    }

    public function testDurObserverDeletedEventRemovesActiveDurFromElastic()
    {
        $countInitialDur = Dur::count();

        $this->observer->shouldReceive('deleteDurFromElastic')->once()->with($countInitialDur + 1);
        $this->observer->shouldReceive('indexElasticDur')->once()->with($countInitialDur + 1);

        $dur = Dur::create([
            'id' => 1,
            'non_gateway_datasets' => ['ICNARC'],
            'non_gateway_applicants' => ['Lila Kilback'],
            'funders_and_sponsors' => ['Newcastle Upon Tyne Hospitals NHS Foundation Trust'],
            'other_approval_committees' => ['REC reference: 14/LO/1965'],
            'gateway_outputs_tools' => [],
            'non_gateway_outputs' => ['https://emj.bmj.com/content/38/9/A2.2'],
            'gateway_outputs_papers' => [],
            'project_title' => 'Birth order and cord blood DNA methylation',
            'project_id_text' => 'B3649',
            'organisation_name' => 'Kings College London',
            'organisation_sector' => 'CQC Registered Health or/and Social Care provider',
            'lay_summary' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'technical_summary' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'latest_approval_date' => Carbon::now(),
            'manual_upload' => 1,
            'rejection_reason' => '',
            'sublicence_arrangements' => '',
            'public_benefit_statement' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'data_sensitivity_level' => '',
            'project_start_date' => Carbon::now(),
            'project_end_date' => Carbon::now(),
            'access_date' => Carbon::now(),
            'accredited_researcher_status' => 'No',
            'confidential_data_description' => '',
            'dataset_linkage_description' => '',
            'duty_of_confidentiality' => 'Not applicable',
            'legal_basis_for_data_article6' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'legal_basis_for_data_article9' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'national_data_optout' => 'No',
            'organisation_id' => 'grid.11201.33',
            'privacy_enhancements' => htmlentities(implode(' ', fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, 'UTF-8'),
            'request_category_type' => 'Health Services & Delivery',
            'request_frequency' => 'Efficacy & Mechanism Evaluation',
            'access_type' => 'Public Health Research',
            'mongo_object_dar_id' => 'MOBJIDDAR-6556',
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'enabled' => 0,
            'last_activity' => Carbon::now(),
            'counter' => 53816,
            'mongo_object_id' => 'MOBJID-5092',
            'mongo_id' => 86611984357,
            'status' => Dur::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('dur', [
            'id' => $countInitialDur + 1,
        ]);

        $dur->delete();

        $this->assertSoftDeleted('dur', ['id' => $countInitialDur + 1]);
    }

}
