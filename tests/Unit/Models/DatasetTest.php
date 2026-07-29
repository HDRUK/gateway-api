<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\DatasetVersion;

class DatasetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Team::flushEventListeners();
    }

    private function makeVersion(Dataset $dataset, int $version, ?string $title): DatasetVersion
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

    public function test_titlesForPids_returns_empty_array_for_empty_input(): void
    {
        $this->assertSame([], Dataset::titlesForPids([]));
    }

    public function test_titlesForPids_returns_title_for_a_known_pid(): void
    {
        $team    = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create([
            'status' => Dataset::STATUS_ACTIVE,
            'pid'    => 'PID-KNOWN',
        ]);
        $this->makeVersion($dataset, 1, 'My Dataset Title');

        $result = Dataset::titlesForPids(['PID-KNOWN']);

        $this->assertSame(['PID-KNOWN' => 'My Dataset Title'], $result);
    }

    public function test_titlesForPids_returns_null_for_pid_with_no_dataset(): void
    {
        $result = Dataset::titlesForPids(['PID-DOES-NOT-EXIST']);

        $this->assertArrayHasKey('PID-DOES-NOT-EXIST', $result);
        $this->assertNull($result['PID-DOES-NOT-EXIST']);
    }

    public function test_titlesForPids_uses_the_latest_version_title_when_several_exist(): void
    {
        $team    = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create([
            'status' => Dataset::STATUS_ACTIVE,
            'pid'    => 'PID-MULTI-VERSION',
        ]);
        $this->makeVersion($dataset, 1, 'Old Title');
        $this->makeVersion($dataset, 2, 'New Title');

        $result = Dataset::titlesForPids(['PID-MULTI-VERSION']);

        $this->assertSame('New Title', $result['PID-MULTI-VERSION']);
    }

    public function test_titlesForPids_returns_null_when_dataset_has_no_version_rows(): void
    {
        $team = Team::factory()->create();
        Dataset::factory()->for($team)->create([
            'status' => Dataset::STATUS_ACTIVE,
            'pid'    => 'PID-NO-VERSION',
        ]);

        $result = Dataset::titlesForPids(['PID-NO-VERSION']);

        $this->assertArrayHasKey('PID-NO-VERSION', $result);
        $this->assertNull($result['PID-NO-VERSION']);
    }

    public function test_titlesForPids_returns_exactly_one_entry_per_input_pid(): void
    {
        $team    = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create([
            'status' => Dataset::STATUS_ACTIVE,
            'pid'    => 'PID-FOUND',
        ]);
        $this->makeVersion($dataset, 1, 'Found Title');

        $result = Dataset::titlesForPids(['PID-FOUND', 'PID-MISSING']);

        $this->assertSame(['PID-FOUND', 'PID-MISSING'], array_keys($result));
        $this->assertSame('Found Title', $result['PID-FOUND']);
        $this->assertNull($result['PID-MISSING']);
    }
}
