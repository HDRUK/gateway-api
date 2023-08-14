<?php

namespace Database\Seeders;

use Hash;
use App\Models\User;
use App\Models\Team;
use App\Models\Role;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasPermission;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create our super user account
        $user = User::factory()->create([
            'name' => 'HDRUK Super-User',
            'firstname' => 'HDRUK',
            'lastname' => 'Super-User',
            'email' => 'developers@hdruk.ac.uk',
            'provider' => 'service',
            'password' => Hash::make('Watch26Task?'),
            'is_admin' => true,
        ]);

        // Assign this account to every single team
        $teams = Team::all();
        foreach ($teams as $team) {
            TeamHasUser::create([
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]);
        }

        // Finally add all permissions for hdruk.superadmin to this
        // user account
        $role = Role::with('permissions')
            ->where('name', 'hdruk.superadmin')->first();

        foreach ($role->permissions as $perm) {
            TeamUserHasPermission::create([
                'team_has_user_id' => $user->id,
                'permission_id' => $perm->id,
            ]);
        }

        User::factory(10)->create();
    }
}
