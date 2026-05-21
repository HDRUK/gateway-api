<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\RemovePublicationDatasetVersionDuplicates;
use App\Models\PublicationHasDatasetVersion;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class RemovePublicationDatasetVersionDuplicatesTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function makeRow(array $attrs): PublicationHasDatasetVersion
    {
        return PublicationHasDatasetVersion::create($attrs);
    }

    public function test_it_does_nothing_when_no_duplicates_exist(): void
    {
        $initRows = PublicationHasDatasetVersion::get();

        PublicationHasDatasetVersion::withTrashed()->forceDelete();
        PublicationHasDatasetVersion::truncate();

        $this->makeRow(['publication_id' => 1, 'dataset_version_id' => 1, 'link_type' => 'ABOUT']);
        $this->makeRow(['publication_id' => 2, 'dataset_version_id' => 2, 'link_type' => 'USING']);

        $this->artisan(RemovePublicationDatasetVersionDuplicates::class)
            ->expectsOutput('No duplicates found. Table is clean.')
            ->assertExitCode(0);

        $this->assertCount(2, PublicationHasDatasetVersion::all());

        PublicationHasDatasetVersion::withTrashed()->forceDelete();
        PublicationHasDatasetVersion::truncate();

        foreach ($initRows as $row) {
            PublicationHasDatasetVersion::create([
                'publication_id' => $row->publication_id,
                'dataset_version_id' => $row->dataset_version_id,
                'link_type' => $row->link_type,
                'description' => $row->description,
            ]);
        }
    }

    public function test_it_removes_duplicates_keeping_lowest_id(): void
    {
        $initRows = PublicationHasDatasetVersion::get();

        PublicationHasDatasetVersion::withTrashed()->forceDelete();
        PublicationHasDatasetVersion::truncate();

        $keep   = $this->makeRow(['publication_id' => 1, 'dataset_version_id' => 1, 'link_type' => 'ABOUT']);
        $dup1   = $this->makeRow(['publication_id' => 1, 'dataset_version_id' => 1, 'link_type' => 'ABOUT']);
        $dup2   = $this->makeRow(['publication_id' => 1, 'dataset_version_id' => 1, 'link_type' => 'ABOUT']);
        // Unique row
        $unique = $this->makeRow(['publication_id' => 2, 'dataset_version_id' => 2, 'link_type' => 'USING']);

        $this->artisan(RemovePublicationDatasetVersionDuplicates::class)
            ->expectsOutput('Done. 2 duplicate row(s) removed.')
            ->assertExitCode(0);

        $this->assertCount(2, PublicationHasDatasetVersion::all());

        $this->assertDatabaseHas('publication_has_dataset_version', ['id' => $keep->id]);
        $this->assertDatabaseHas('publication_has_dataset_version', ['id' => $unique->id]);

        $checkDup1 = PublicationHasDatasetVersion::where('id', $dup1->id)->first();
        $this->assertNull($checkDup1);

        $checkDup2 = PublicationHasDatasetVersion::where('id', $dup2->id)->first();
        $this->assertNull($checkDup2);

        PublicationHasDatasetVersion::withTrashed()->forceDelete();
        PublicationHasDatasetVersion::truncate();

        foreach ($initRows as $row) {
            PublicationHasDatasetVersion::create([
                'publication_id' => $row->publication_id,
                'dataset_version_id' => $row->dataset_version_id,
                'link_type' => $row->link_type,
                'description' => $row->description,
            ]);
        }
    }
}
