<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Dur;
use App\Models\Team;
use App\Models\Sector;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\DatasetVersion;
use App\Models\DataProviderColl;
use App\Models\DurHasDatasetVersion;
use App\Models\DataProviderCollHasTeam;

class DurTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Team::flushEventListeners();
        Dur::flushEventListeners();
        Collection::flushEventListeners();
        DurHasDatasetVersion::flushEventListeners();
    }

    public function test_facets_reflect_team_sector_and_linked_active_dataset(): void
    {
        $team = Team::factory()->create(['name' => 'Our Future Health']);
        $sector = Sector::factory()->create(['name' => 'Charity/Non-profit']);

        $dur = Dur::create([
            'project_title' => 'Linked Project',
            'team_id'       => $team->id,
            'sector_id'     => $sector->id,
            'access_type'   => 'TRE',
            'status'        => Dur::STATUS_ACTIVE,
        ]);

        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $version = DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => 'Full Title',
            'short_title' => 'Short Title',
        ]);
        DurHasDatasetVersion::create(['dur_id' => $dur->id, 'dataset_version_id' => $version->id]);

        $collection = Collection::factory()->create(['status' => Collection::STATUS_ACTIVE, 'name' => 'Linked Collection']);
        $collection->dur()->attach($dur->id);

        $network = DataProviderColl::factory()->create(['name' => 'Linked Network']);
        DataProviderCollHasTeam::create(['data_provider_coll_id' => $network->id, 'team_id' => $team->id]);

        $searchable = $dur->fresh()->toSearchableArray();

        $this->assertEquals('Our Future Health', $searchable['publisherName']);
        $this->assertEquals('Charity/Non-profit', $searchable['sector']);
        $this->assertEquals(['Short Title'], $searchable['datasetTitles']);
        $this->assertEquals(['Linked Collection'], $searchable['collectionNames']);
        $this->assertEquals(['Linked Network'], $searchable['dataProviderColl']);
        $this->assertEquals('TRE', $searchable['accessType']);
    }

    public function test_facets_exclude_inactive_datasets(): void
    {
        $team = Team::factory()->create();
        $dur = Dur::create(['project_title' => 'Project', 'team_id' => $team->id, 'status' => Dur::STATUS_ACTIVE]);

        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ARCHIVED]);
        $version = DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => 'Archived Title',
            'short_title' => 'Archived Title',
        ]);
        DurHasDatasetVersion::create(['dur_id' => $dur->id, 'dataset_version_id' => $version->id]);

        $searchable = $dur->fresh()->toSearchableArray();

        $this->assertEquals([], $searchable['datasetTitles']);
    }
}
