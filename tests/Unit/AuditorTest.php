<?php

namespace Tests\Unit;

use Auditor;

use Tests\TestCase;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\MinimalUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditorTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
        ]);
    }

    public function test_it_can_audit(): void
    {
        $userId = User::all()->random()->id;
        $teamId = Team::all()->random()->id;
        $targetUserId = User::all()->random()->id;
        $targetTeamId = Team::all()->random()->id;
        $actionType = 'CREATE';
        $actionService = 'Gateway API';
        $description = 'testing auditor description';

        $logInfo = [
            'user_id' => $userId,
            'team_id' => $teamId,
            'target_user_id' => $targetUserId,
            'target_team_id' => $targetTeamId,
            'action_type' => $actionType,
            'action_service' =>  $actionService,
            'description' => $description,
        ];
        Auditor::log($logInfo);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $userId,
            'team_id' => $teamId,
            'target_user_id' => $targetUserId,
            'target_team_id' => $targetTeamId,
            'action_type' => $actionType,
            'action_service' =>  $actionService,
            'description' => $description,
        ]);
    }

}