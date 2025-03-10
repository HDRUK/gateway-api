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
use Database\Seeders\TagSeeder;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TypeCategorySeeder;
use Database\Seeders\DatasetVersionSeeder;
use App\Models\CollectionHasDatasetVersion;
use Database\Seeders\SpatialCoverageSeeder;
use Database\Seeders\CollectionHasUserSeeder;
use Database\Seeders\ProgrammingPackageSeeder;
use Database\Seeders\ProgrammingLanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DurObserverTest extends TestCase
{
    use RefreshDatabase;
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

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
            CategorySeeder::class,
            TypeCategorySeeder::class,
            ProgrammingLanguageSeeder::class,
            ProgrammingPackageSeeder::class,
            LicenseSeeder::class,
            TagSeeder::class,
            ApplicationSeeder::class,
            CollectionSeeder::class,
            CollectionHasUserSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            KeywordSeeder::class,
            ToolSeeder::class,
        ]);
        $this->userId = User::all()->random()->id;
        $this->teamId = Team::all()->random()->id;
    }

    public function testDurObserverCreatedEventIndexesActiveDur()
    {
        $observer = Mockery::mock(DurObserver::class)->makePartial();
        app()->instance(DurObserver::class, $observer);
        $observer->shouldReceive('indexElasticDur')->once()->with(1);

        $userId = User::all()->random()->id;
        $teamId = Team::all()->random()->id;

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
            'id' => $dur->id,
            'project_title' => 'Birth order and cord blood DNA methylation',
            'project_id_text' => 'B3649',
            'organisation_name' => 'Kings College London',
            'status' => Dur::STATUS_ACTIVE,
        ]);
    }

    public function testDurObserverUpdatedEventDeletesActiveDur()
    {
        $observer = Mockery::mock(DurObserver::class)->makePartial();
        app()->instance(DurObserver::class, $observer);
        $observer->shouldReceive('indexElasticDur')->once()->with(1);

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

        $observer = Mockery::mock(DurObserver::class)->makePartial();
        $observer->shouldReceive('deleteDurFromElastic')->once()->with(1);

        app()->instance(DurObserver::class, $observer);

        $dur->update(['status' => Dur::STATUS_ARCHIVED]);

        $this->assertDatabaseHas('dur', [
            'id' => $dur->id,
            'status' => Dur::STATUS_ARCHIVED,
        ]);
    }

    public function testDurObserverUpdatedEventIndexesWhenStatusBecomesActive()
    {
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
            'status' => Dur::STATUS_DRAFT,
        ]);

        $observer = Mockery::mock(DurObserver::class)->makePartial();
        $observer->shouldReceive('indexElasticDur')->once()->with(1);
        app()->instance(DurObserver::class, $observer);

        $dur->update(['status' => Dur::STATUS_ACTIVE]);

        $this->assertDatabaseHas('dur', [
            'id' => $dur->id,
            'status' => Dur::STATUS_ACTIVE
        ]);
    }

    public function testDurObserverDeletedEventRemovesActiveDurFromElastic()
    {
        $observer = Mockery::mock(DurObserver::class)->makePartial();
        $observer->shouldReceive('deleteDurFromElastic')->once()->with(1);
        $observer->shouldReceive('indexElasticDur')->once()->with(1);
        app()->instance(DurObserver::class, $observer);

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
            'id' => 1,
        ]);

        $dur->delete();

        $this->assertSoftDeleted('dur', ['id' => 1]);
    }

}
