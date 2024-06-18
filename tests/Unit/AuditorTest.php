<?php

namespace Tests\Unit;

use Auditor;

use Tests\TestCase;

use App\Models\Team;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Config;
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
        Config::set('GOOGLE_CLOUD_PROJECT_ID', 'fake-project-id');
        Config::set('GOOGLE_CLOUD_PUBSUB_TOPIC', 'fake-topic-name');
        Config::set('GOOGLE_CLOUD_PUBSUB_ENABLED', false);

        $userId = User::all()->random()->id;
        $teamId = Team::all()->random()->id;
        $targetUserId = User::all()->random()->id;
        $targetTeamId = Team::all()->random()->id;
        $actionType = 'CREATE';
        $actionName = 'Gateway API';
        $description = 'testing auditor description';

        $logInfo = [
            'user_id' => $userId,
            'team_id' => $teamId,
            'target_user_id' => $targetUserId,
            'target_team_id' => $targetTeamId,
            'action_type' => $actionType,
            'action_name' =>  $actionName,
            'description' => $description,
        ];
        Auditor::log($logInfo);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $userId,
            'team_id' => $teamId,
            'target_user_id' => $targetUserId,
            'target_team_id' => $targetTeamId,
            'action_type' => $actionType,
            'action_name' =>  strtolower($actionName),
            'description' => $description,
        ]);
    }

}