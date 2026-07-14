<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\NormalisePublisherGatewayId;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Coverage for the publisher gatewayId backfill:
 *   - snapshot rows: fix summary.publisher.gatewayId in the full envelope;
 *   - delta rows: fix a /summary/publisher/gatewayId patch op value;
 *   - --dry-run makes no changes.
 */
class NormalisePublisherGatewayIdTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected function setUp(): void
    {
        $this->commonSetUp();

        // Avoid Elasticsearch indexing / job dispatch on version writes.
        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    private function snapshotEnvelope(int|string $gatewayId, string $title = 'T'): array
    {
        return [
            'gwdmVersion' => Config::get('metadata.GWDM.version'),
            'metadata' => [
                'summary' => [
                    'title' => $title,
                    'publisher' => ['gatewayId' => $gatewayId, 'name' => 'Org'],
                ],
            ],
            'original_metadata' => ['summary' => ['publisher' => ['gatewayId' => $gatewayId]]],
        ];
    }

    public function test_backfills_snapshot_gateway_id_to_pid(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        // gatewayId wrongly stored as the numeric primary key.
        $dv = DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'patch' => null,
            'metadata' => $this->snapshotEnvelope((string) $team->id),
            'title' => 'T',
            'short_title' => 'T',
        ]);

        $this->artisan(NormalisePublisherGatewayId::class)->assertExitCode(0);

        $fresh = DatasetVersion::find($dv->id);
        $this->assertSame($team->pid, $fresh->metadata['metadata']['summary']['publisher']['gatewayId']);
        // original_metadata left untouched (audit trail).
        $this->assertSame((string) $team->id, $fresh->metadata['original_metadata']['summary']['publisher']['gatewayId']);
    }

    public function test_backfills_delta_patch_op_value_to_pid(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'patch' => null,
            'metadata' => $this->snapshotEnvelope($team->pid),
            'title' => 'T',
            'short_title' => 'T',
        ]);

        $delta = DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 2,
            'patch' => [[
                'op' => 'replace',
                'path' => '/summary/publisher/gatewayId',
                'value' => (string) $team->id,
            ]],
            'metadata' => [],
            'title' => 'T',
            'short_title' => 'T',
        ]);

        $this->artisan(NormalisePublisherGatewayId::class)->assertExitCode(0);

        $fresh = DatasetVersion::find($delta->id);
        $this->assertSame($team->pid, $fresh->patch[0]['value']);
    }

    public function test_dry_run_makes_no_changes(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        $dv = DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'patch' => null,
            'metadata' => $this->snapshotEnvelope((string) $team->id),
            'title' => 'T',
            'short_title' => 'T',
        ]);

        $this->artisan(NormalisePublisherGatewayId::class, ['--dry-run' => true])->assertExitCode(0);

        $fresh = DatasetVersion::find($dv->id);
        // Unchanged: still the raw primary key.
        $this->assertSame((string) $team->id, $fresh->metadata['metadata']['summary']['publisher']['gatewayId']);
    }
}
