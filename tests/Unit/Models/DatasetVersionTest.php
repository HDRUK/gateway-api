<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\Gwdm\SourceSchema;

class DatasetVersionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Team::flushEventListeners();
    }

    private function makeVersion(Dataset $dataset, int $version, string $title): DatasetVersion
    {
        return DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => $version,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => $title,
            'short_title' => $title,
        ]);
    }

    public function test_scopeIndexEligible_matches_shouldBeSearchable_for_latest_version(): void
    {
        $team = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        $v1 = $this->makeVersion($dataset, 1, 'v1');
        $v2 = $this->makeVersion($dataset, 2, 'v2');

        $this->assertFalse($v1->fresh()->shouldBeSearchable());
        $this->assertTrue($v2->fresh()->shouldBeSearchable());

        $eligibleIds = DatasetVersion::indexEligible()->pluck('id');

        $this->assertFalse($eligibleIds->contains($v1->id));
        $this->assertTrue($eligibleIds->contains($v2->id));
    }

    public function test_scopeIndexEligible_excludes_versions_of_an_inactive_dataset(): void
    {
        $team = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ARCHIVED]);

        $v1 = $this->makeVersion($dataset, 1, 'v1');

        $this->assertFalse($v1->fresh()->shouldBeSearchable());
        $this->assertFalse(DatasetVersion::indexEligible()->pluck('id')->contains($v1->id));
    }

    public function test_scopeIndexEligible_treats_a_deleted_latest_version_as_not_the_latest(): void
    {
        $team = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        $v1 = $this->makeVersion($dataset, 1, 'v1');
        $v2 = $this->makeVersion($dataset, 2, 'v2');
        $v2->delete();

        $this->assertTrue($v1->fresh()->shouldBeSearchable());

        $eligibleIds = DatasetVersion::indexEligible()->pluck('id');

        $this->assertTrue($eligibleIds->contains($v1->id));
        $this->assertFalse($eligibleIds->contains($v2->id));
    }

    public function test_scopeIndexEligible_excludes_versions_whose_owning_team_is_soft_deleted(): void
    {
        $deletedTeam = Team::factory()->create();
        $dataset = Dataset::factory()->for($deletedTeam)->create(['status' => Dataset::STATUS_ACTIVE]);
        $v1 = $this->makeVersion($dataset, 1, 'v1');
        $deletedTeam->delete();

        $this->assertFalse($v1->fresh()->shouldBeSearchable());
        $this->assertFalse($v1->fresh()->load('dataset.team')->shouldBeSearchable());
        $this->assertFalse(DatasetVersion::indexEligible()->pluck('id')->contains($v1->id));
    }

    public function test_submittedMetadata_unwraps_a_stored_form_envelope(): void
    {
        $envelope = ['original_metadata' => [
            'schemaModel' => 'HDRUK',
            'schemaVersion' => '4.1.0',
            'metadata' => ['summary' => ['title' => 'Example']],
        ]];

        $this->assertSame(['summary' => ['title' => 'Example']], DatasetVersion::submittedMetadata($envelope));
    }

    public function test_submittedMetadata_returns_a_plain_original_as_is(): void
    {
        $envelope = ['original_metadata' => ['summary' => ['title' => 'Example']]];

        $this->assertSame(['summary' => ['title' => 'Example']], DatasetVersion::submittedMetadata($envelope));
    }

    public function test_sourceSchema_prefers_its_columns(): void
    {
        $version = new DatasetVersion([
            'source_schema_model' => 'CRUK',
            'source_schema_version' => '1.0.0',
            'metadata' => ['schemaModel' => 'HDRUK', 'schemaVersion' => '4.1.0'],
        ]);

        $this->assertSame(['model' => 'CRUK', 'version' => '1.0.0'], SourceSchema::fromVersion($version)->toArray());
    }

    public function test_sourceSchema_falls_back_to_its_envelope(): void
    {
        $version = new DatasetVersion([
            'metadata' => ['schemaModel' => 'HDRUK', 'schemaVersion' => '4.1.0'],
        ]);

        $this->assertSame(['model' => 'HDRUK', 'version' => '4.1.0'], SourceSchema::fromVersion($version)->toArray());
    }
}
