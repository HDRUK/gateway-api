<?php

namespace Tests\Unit;

use Auditor;

use Tests\TestCase;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditorTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_it_can_audit(): void
    {
        $userId = User::all()->random()->id;
        $teamId = Team::all()->random()->id;
        $actionType = 'CREATE';
        $actionService = 'Gateway API';
        $description = 'testing auditor description';

        Auditor::log($userId, $teamId, $actionType, $actionService, $description);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $userId,
            'team_id' => $teamId,
            'action_type' => $actionType,
            'action_service' =>  $actionService,
            'description' => $description,
        ]);
    }

}