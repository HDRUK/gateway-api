<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SDENetworkConciergeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This Seeder creates a team and user for the SDE Network Concierge
     */
    public function run(): void
    {
        // Create team
        $team = Team::create([
            'name' => 'SDE Network Concierge',
            'pid' => (string) Str::uuid(),
            'allows_messaging' => 1,
            'workflow_enabled' =>  1,
            'access_requests_management' => 1,
            'uses_5_safes' => 1,
            'is_admin' => 0,
            'member_of' => 'OTHER',
            'contact_point' => 'data.healthresearch@nhs.net',
            'enabled' => 1,
        ]);

        // Create user
        $user = User::create([
            'name' => 'SDE Network Concierge',
            'firstname' => 'SDE',
            'lastname' => 'Network Concierge',
            'email' => 'data.healthresearch@nhs.net',
            'provider' => 'service',
            'password' => '$2y$10$5yALZeLgi0AutXiu5qnLoe6QpapTV4tZR5At3cigVXDZi06lV.Vr6',
            'is_admin' => 0,
        ]);

        // Add user and superadmin to team
        $thuId = TeamHasUser::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $superAdminIds = User::where('is_admin', true)->pluck('id');
        foreach ($superAdminIds as $adminId) {
            TeamHasUser::create([
                'team_id' => $team->id,
                'user_id' => $adminId
            ]);
        }

        // Make the user the dar admin for the team
        $r = Role::where('name', 'custodian.dar.manager')->first();

        TeamUserHasRole::create([
            'team_has_user_id' => $thuId->id,
            'role_id' => $r->id,
        ]);
    }
}
