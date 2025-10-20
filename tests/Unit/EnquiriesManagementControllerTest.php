<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use Tests\Traits\MockExternalApis;
use App\Http\Traits\EnquiriesTrait;

class EnquiriesManagementControllerTest extends TestCase
{
    use EnquiriesTrait;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_it_can_determine_dar_managers_from_team_id(): void
    {
        // Create Teams
        $team1 = Team::factory(1)->create([
            'name' => 'Ghostbusters',
            'enabled' => true,
        ])->first();

        $team2 = Team::factory(1)->create([
            'name' => 'Thundercats',
            'enabled' => true,
        ])->first();

        // Create Users
        $user1 = User::factory(1)->create([
            'firstname' => 'Peter',
            'lastname' => 'Venkman',
            'email' => 'peter.venkman@ghostbusters.com',
        ])->first();

        $user2 = User::factory(1)->create([
            'firstname' => 'Lion',
            'lastname' => 'Oh',
            'email' => 'lion.oh@thundercats.com',
        ])->first();

        $users = User::factory(2)->create();

        $role = Role::where('name', 'custodian.dar.manager')->first();
        $roleOther = Role::where('name', 'hdruk.dar')->first();
        $roleOtherStill = Role::where('name', 'developer')->first();

        $teamHasUser1 = TeamHasUser::create([
            'user_id' => $user1->id,
            'team_id' => $team1->id,
        ]);

        $teamHasUser2 = TeamHasUser::create([
            'user_id' => $user2->id,
            'team_id' => $team2->id,
        ]);

        TeamUserHasRole::create([
            'team_has_user_id' => $teamHasUser1->id,
            'role_id' => $role->id,
        ]);

        TeamUserHasRole::create([
            'team_has_user_id' => $teamHasUser2->id,
            'role_id' => $role->id,
        ]);

        foreach ($users as $u) {
            $teamHasAdditionalUsers = TeamHasUser::create([
                'user_id' => $u->id,
                'team_id' => fake()->randomElement([$team1->id, $team2->id]),
            ]);

            TeamUserHasRole::create([
                'team_has_user_id' => $teamHasAdditionalUsers->id,
                'role_id' => fake()->randomElement([$roleOther->id, $roleOtherStill->id]),
            ]);
        }

        $users = $this->getUsersByTeamIds([$team1->id], 1);
        $this->assertEquals(count($users), 1);

        $this->assertEquals($users[0]['user']['email'], 'peter.venkman@ghostbusters.com');
        $this->assertEquals($users[0]['team']['id'], $team1->id);
    }
}
