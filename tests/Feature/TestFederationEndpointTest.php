<?php

namespace Tests\Feature;

use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use App\Services\GoogleSecretManagerService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class TestFederationEndpointTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const BASE_URL = 'https://test-federation.example.com';
    private const DATASETS_PATH = '/api/v1/datasets';
    private const DATASET_PATH = '/api/v1/datasets/{id}';

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function makeFederationForTeam(): array
    {
        $team = Team::factory()->create();

        $federation = Federation::factory()->create([
            'auth_type' => 'NO_AUTH',
            'endpoint_baseurl' => self::BASE_URL,
            'endpoint_datasets' => self::DATASETS_PATH,
            'endpoint_dataset' => self::DATASET_PATH,
            'error' => true,
            'error_text' => 'a previous connection failure',
            'enabled' => false,
        ]);

        TeamHasFederation::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
        ]);

        return [$team, $federation];
    }

    private function testPayload(Federation $federation): array
    {
        return [
            'id' => $federation->id,
            'auth_type' => 'NO_AUTH',
            'endpoint_baseurl' => $federation->endpoint_baseurl,
            'endpoint_datasets' => $federation->endpoint_datasets,
            'endpoint_dataset' => $federation->endpoint_dataset,
            'run_time_hour' => $federation->run_time_hour,
        ];
    }

    public function test_successful_test_clears_error_for_existing_federation(): void
    {
        [$team, $federation] = $this->makeFederationForTeam();
        $this->mock(GoogleSecretManagerService::class);

        Http::fake([
            self::BASE_URL . self::DATASETS_PATH . '*' => Http::response(['items' => []], 200),
        ]);

        $response = $this->json(
            'POST',
            'api/v1/teams/' . $team->id . '/federations/test',
            $this->testPayload($federation),
            $this->header,
        );

        $response->assertStatus(200);

        $fresh = $federation->fresh();
        $this->assertFalse($fresh->error);
        $this->assertNull($fresh->error_text);
    }

    public function test_failed_test_does_not_change_persisted_error_state(): void
    {
        [$team, $federation] = $this->makeFederationForTeam();
        $this->mock(GoogleSecretManagerService::class);

        Http::fake([
            self::BASE_URL . self::DATASETS_PATH . '*' => Http::response(['detail' => 'Invalid token'], 401),
        ]);

        $response = $this->json(
            'POST',
            'api/v1/teams/' . $team->id . '/federations/test',
            $this->testPayload($federation),
            $this->header,
        );

        $response->assertStatus(200);

        $fresh = $federation->fresh();
        $this->assertTrue($fresh->error);
        $this->assertSame('a previous connection failure', $fresh->error_text);
        $this->assertFalse($fresh->enabled);
    }

    public function test_successful_test_does_not_clear_error_for_a_different_teams_federation(): void
    {
        [, $federation] = $this->makeFederationForTeam();
        $otherTeam = Team::factory()->create();
        $this->mock(GoogleSecretManagerService::class);

        Http::fake([
            self::BASE_URL . self::DATASETS_PATH . '*' => Http::response(['items' => []], 200),
        ]);

        $response = $this->json(
            'POST',
            'api/v1/teams/' . $otherTeam->id . '/federations/test',
            $this->testPayload($federation),
            $this->header,
        );

        $response->assertStatus(200);

        $fresh = $federation->fresh();
        $this->assertTrue($fresh->error);
        $this->assertSame('a previous connection failure', $fresh->error_text);
    }
}
