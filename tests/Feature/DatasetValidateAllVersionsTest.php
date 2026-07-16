<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use MetadataManagementController as MMC;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * DatasetService::validateAllVersions() is currently unwired — no route or
 * controller calls it yet. It's staged ahead of PR4 (GWDM 3.0 activation),
 * which is its intended consumer (see docs/GAT-9018-pr-plan.md). Exercised
 * directly against the service rather than through HTTP for that reason.
 */
class DatasetValidateAllVersionsTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
        $this->disableObservers();
    }

    private function setGwdmHeader(string $version): void
    {
        request()->headers->set('x-gwdm-version', $version);
    }

    private function service(): DatasetService
    {
        return app(DatasetService::class);
    }

    public function test_validate_all_versions_reports_each_stored_version(): void
    {
        $team = Team::first();
        $user = User::first();
        $metadata = $this->getMetadataV2p0();

        $this->setGwdmHeader('2.0');
        $created = $this->service()->create(
            [
                'metadata' => $metadata,
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            null,
            null,
            false,
        );
        $datasetId = $created['dataset_id'];

        $this->setGwdmHeader('2.0');
        $this->service()->update(
            Dataset::find($datasetId),
            ['metadata' => $metadata, 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            $team->id,
            Dataset::ORIGIN_MANUAL,
            false,
            $team,
        );

        MMC::shouldReceive('validateDataModelTypeWithErrors')
            ->andReturn(['valid' => true, 'errors' => null]);

        $results = $this->service()->validateAllVersions(Dataset::find($datasetId));

        $this->assertCount(2, $results);
        $this->assertSame(1, $results[0]['version']);
        $this->assertSame(2, $results[1]['version']);
        $this->assertTrue($results[0]['valid']);
        $this->assertTrue($results[1]['valid']);
    }

    /**
     * One version failing TRASER validation must not abort the rest of the
     * loop — each version is validated independently.
     */
    public function test_validate_all_versions_degrades_gracefully_when_one_version_fails(): void
    {
        $team = Team::first();
        $user = User::first();

        $this->setGwdmHeader('2.0');
        $created = $this->service()->create(
            [
                'metadata' => $this->getMetadataV2p0(),
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            null,
            null,
            false,
        );
        $datasetId = $created['dataset_id'];

        $this->setGwdmHeader('2.1');
        $this->service()->update(
            Dataset::find($datasetId),
            ['metadata' => $this->getMetadataV2p1(), 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            $team->id,
            Dataset::ORIGIN_MANUAL,
            false,
            $team,
        );

        MMC::shouldReceive('validateDataModelTypeWithErrors')
            ->andReturnUsing(function ($json, $schema, $gwdmVersion) {
                if ($gwdmVersion === '2.1') {
                    throw new \RuntimeException('TRASER unreachable');
                }
                return ['valid' => true, 'errors' => null];
            });

        $results = $this->service()->validateAllVersions(Dataset::find($datasetId));

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['valid'], 'v1 (2.0) must validate successfully');
        $this->assertFalse($results[1]['valid'], 'v2 (2.1) must degrade to valid=false rather than abort the loop');
        $this->assertSame('TRASER unreachable', $results[1]['errors']);
    }
}
