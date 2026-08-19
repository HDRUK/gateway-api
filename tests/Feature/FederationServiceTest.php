<?php

namespace Tests\Feature;

use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use App\Services\FederationService;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class FederationServiceTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_update_clears_a_previously_recorded_error(): void
    {
        $team = Team::factory()->create();

        $federation = Federation::factory()->create([
            'error' => true,
            'error_text' => 'a previous connection failure',
        ]);

        TeamHasFederation::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
        ]);

        (new FederationService())->update($team->id, $federation->id, [
            'federation_type' => $federation->federation_type,
            'auth_type' => 'NO_AUTH',
            'endpoint_baseurl' => $federation->endpoint_baseurl,
            'endpoint_datasets' => $federation->endpoint_datasets,
            'endpoint_dataset' => $federation->endpoint_dataset,
            'run_time_hour' => $federation->run_time_hour,
            'run_time_minute' => '00',
            'enabled' => true,
            'notifications' => [],
        ]);

        $fresh = $federation->fresh();

        $this->assertFalse($fresh->error);
        $this->assertNull($fresh->error_text);
    }
}
