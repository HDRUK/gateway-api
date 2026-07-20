<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\DatasetVersion;

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
}
