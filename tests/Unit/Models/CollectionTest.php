<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\DatasetVersion;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\CollectionHasDatasetVersion;

class CollectionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Team::flushEventListeners();
        Collection::flushEventListeners();
        CollectionHasDatasetVersion::flushEventListeners();
    }

    public function test_facets_reflect_owning_team_and_linked_active_dataset(): void
    {
        $team = Team::factory()->create(['name' => 'Cystic Fibrosis Trust']);
        $collection = Collection::factory()->for($team)->create(['status' => Collection::STATUS_ACTIVE]);

        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $version = DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => 'Full Title',
            'short_title' => 'Short Title',
        ]);
        CollectionHasDatasetVersion::create(['collection_id' => $collection->id, 'dataset_version_id' => $version->id]);

        $network = DataProviderColl::factory()->create(['name' => 'Linked Network']);
        DataProviderCollHasTeam::create(['data_provider_coll_id' => $network->id, 'team_id' => $team->id]);

        $searchable = $collection->fresh()->toSearchableArray();

        $this->assertEquals('Cystic Fibrosis Trust', $searchable['publisherName']);
        $this->assertEquals(['Short Title'], $searchable['datasetTitles']);
        $this->assertEquals(['Linked Network'], $searchable['dataProviderColl']);
    }

    public function test_facets_exclude_inactive_datasets(): void
    {
        $team = Team::factory()->create();
        $collection = Collection::factory()->for($team)->create(['status' => Collection::STATUS_ACTIVE]);

        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ARCHIVED]);
        $version = DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => 'Archived Title',
            'short_title' => 'Archived Title',
        ]);
        CollectionHasDatasetVersion::create(['collection_id' => $collection->id, 'dataset_version_id' => $version->id]);

        $searchable = $collection->fresh()->toSearchableArray();

        $this->assertEquals([], $searchable['datasetTitles']);
    }

    public function test_scopeIndexEligible_matches_shouldBeSearchable(): void
    {
        $active = Collection::factory()->create(['status' => Collection::STATUS_ACTIVE]);
        $archived = Collection::factory()->create(['status' => Collection::STATUS_ARCHIVED]);

        $eligibleIds = Collection::indexEligible()->pluck('id');

        $this->assertTrue($eligibleIds->contains($active->id));
        $this->assertFalse($eligibleIds->contains($archived->id));
    }

    public function test_publisher_name_and_data_provider_coll_are_empty_when_team_has_no_network(): void
    {
        $team = Team::factory()->create(['name' => 'Standalone Custodian']);
        $collection = Collection::factory()->for($team)->create(['status' => Collection::STATUS_ACTIVE]);

        $searchable = $collection->fresh()->toSearchableArray();

        $this->assertEquals('Standalone Custodian', $searchable['publisherName']);
        $this->assertEquals([], $searchable['dataProviderColl']);
    }
}
