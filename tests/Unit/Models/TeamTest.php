<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Team;
use App\Models\Alias;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\Publication;
use App\Models\DatasetVersion;
use App\Models\DurHasDatasetVersion;
use App\Models\DatasetVersionHasTool;
use App\Models\CollectionHasDatasetVersion;
use App\Models\PublicationHasDatasetVersion;

class TeamTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Team::flushEventListeners();
        Dur::flushEventListeners();
        Publication::flushEventListeners();
        Collection::flushEventListeners();
        DurHasDatasetVersion::flushEventListeners();
        PublicationHasDatasetVersion::flushEventListeners();
        CollectionHasDatasetVersion::flushEventListeners();
    }

    public function test_shouldBeSearchable_is_false_when_disabled(): void
    {
        $team = Team::factory()->create(['enabled' => false]);

        $this->assertFalse($team->shouldBeSearchable());
    }

    public function test_shouldBeSearchable_is_true_when_enabled_and_not_deleted(): void
    {
        $team = Team::factory()->create(['enabled' => true]);

        $this->assertTrue($team->shouldBeSearchable());
    }

    public function test_scopeIndexEligible_matches_shouldBeSearchable(): void
    {
        $enabled = Team::factory()->create(['enabled' => true]);
        $disabled = Team::factory()->create(['enabled' => false]);

        $eligibleIds = Team::indexEligible()->pluck('id');

        $this->assertTrue($eligibleIds->contains($enabled->id));
        $this->assertFalse($eligibleIds->contains($disabled->id));
    }

    public function test_name_attribute_is_not_shadowed_by_the_declared_property(): void
    {
        $team = Team::factory()->create(['name' => 'Scottish Safe Haven Network']);

        $this->assertEquals('Scottish Safe Haven Network', $team->toSearchableArray()['name']);
    }

    public function test_facets_and_cross_entity_text_reflect_linked_active_dataset(): void
    {
        $team = Team::factory()->create(['enabled' => true]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $version = DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => [
                'gwdmVersion' => config('metadata.GWDM.version'),
                'metadata'    => ['summary' => ['datasetType' => 'Health and disease;,;Registry']],
            ],
            'title'       => 'Full Title',
            'short_title' => 'Short Title',
        ]);

        $dur = Dur::create(['project_title' => 'Linked Project', 'team_id' => $team->id, 'status' => Dur::STATUS_ACTIVE]);
        DurHasDatasetVersion::create(['dur_id' => $dur->id, 'dataset_version_id' => $version->id]);

        $tool = Tool::factory()->create(['name' => 'Linked Tool']);
        DatasetVersionHasTool::create(['dataset_version_id' => $version->id, 'tool_id' => $tool->id]);

        $publication = Publication::factory()->create(['paper_title' => 'Linked Paper']);
        PublicationHasDatasetVersion::create(['publication_id' => $publication->id, 'dataset_version_id' => $version->id, 'link_type' => 'USING']);

        $collection = Collection::factory()->create(['name' => 'Linked Collection', 'status' => Collection::STATUS_ACTIVE]);
        CollectionHasDatasetVersion::create(['collection_id' => $collection->id, 'dataset_version_id' => $version->id]);

        Alias::create(['name' => 'Also Known As']);
        $team->aliases()->attach(Alias::where('name', 'Also Known As')->first()->id);

        $searchable = $team->fresh()->toSearchableArray();

        $this->assertEquals(['Short Title'], $searchable['datasetTitles']);
        $this->assertEquals(['Health and disease', 'Registry'], $searchable['dataType']);
        $this->assertEquals('Linked Project', $searchable['durTitles']);
        $this->assertEquals('Linked Tool', $searchable['toolNames']);
        $this->assertEquals('Linked Paper', $searchable['publicationTitles']);
        $this->assertEquals('Linked Collection', $searchable['collectionNames']);
        $this->assertEquals(['Also Known As'], $searchable['teamAliases']);
    }

    public function test_facets_exclude_inactive_datasets(): void
    {
        $team = Team::factory()->create(['enabled' => true]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ARCHIVED]);
        DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => ['summary' => ['datasetType' => 'Registry']]],
            'title'       => 'Archived Title',
            'short_title' => 'Archived Title',
        ]);

        $searchable = $team->fresh()->toSearchableArray();

        $this->assertEquals([], $searchable['datasetTitles']);
        $this->assertEquals([], $searchable['dataType']);
        $this->assertEquals('', $searchable['durTitles']);
        $this->assertEquals('', $searchable['toolNames']);
    }
}
