<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\DatasetVersion;
use App\Models\PublicationHasDatasetVersion;

class PublicationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Publication::flushEventListeners();
        PublicationHasDatasetVersion::flushEventListeners();
    }

    public function test_publication_type_splits_comma_delimited_string_into_array(): void
    {
        $publication = Publication::factory()->create(['publication_type' => 'Research articles,Preprints']);

        $this->assertEquals(
            ['Research articles', 'Preprints'],
            $publication->toSearchableArray()['publicationType']
        );
    }

    public function test_publication_type_defaults_empty_segment_to_research_articles(): void
    {
        $publication = Publication::factory()->create(['publication_type' => '']);

        $this->assertEquals(
            ['Research articles'],
            $publication->toSearchableArray()['publicationType']
        );
    }

    public function test_dataset_titles_and_link_types_reflect_linked_active_datasets(): void
    {
        $team = Team::factory()->create(['enabled' => true]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $version = DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => 'National Neonatal Research Database (NNRD)',
            'short_title' => 'NNRD',
        ]);

        $publication = Publication::factory()->create();
        PublicationHasDatasetVersion::create([
            'publication_id'      => $publication->id,
            'dataset_version_id'  => $version->id,
            'link_type'           => 'USING',
        ]);

        $searchable = $publication->fresh()->toSearchableArray();

        $this->assertEquals(['National Neonatal Research Database (NNRD)'], $searchable['datasetTitles']);
        $this->assertEquals(['Using a dataset'], $searchable['datasetLinkTypes']);
    }

    public function test_dataset_titles_exclude_inactive_datasets(): void
    {
        $team = Team::factory()->create(['enabled' => true]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ARCHIVED]);
        $version = DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => 'Archived Dataset',
            'short_title' => 'Archived Dataset',
        ]);

        $publication = Publication::factory()->create();
        PublicationHasDatasetVersion::create([
            'publication_id'      => $publication->id,
            'dataset_version_id'  => $version->id,
            'link_type'           => 'ABOUT',
        ]);

        $searchable = $publication->fresh()->toSearchableArray();

        $this->assertEquals([], $searchable['datasetTitles']);
        $this->assertEquals([], $searchable['datasetLinkTypes']);
    }
}
