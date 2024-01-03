<?php

namespace Database\Demo;

use Hash;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamHasUser;
use App\Models\UserHasRole;
use App\Models\TeamUserHasRole;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserStartDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create our super user account
        $this->createUser('HDRUK', 'Super-User', 'developers@hdruk.ac.uk', 'Watch26Task?', 'service', true, ['hdruk.superadmin']);

        // Create our service layer user account
        // TODO - Need to review permissions for this account overall as superadmin may be too much depending
        // on actual needs
        $this->createUser('HDRUK', 'Service-User', 'services@hdruk.ac.uk', 'Flood?15Voice', 'service', true, ['hdruk.superadmin']);
    }

    /**
     * Generically creates users per passed params
     * 
     * @param string $firstname     The firstname of the user to create
     * @param string $lastname      The lastname of the user to create
     * @param string $email         The email address of the user to create
     * @param string $password      The password of the user to create
     * @param string $provider      The provider of the user to create
     * @param bool $isAdmin         Whether this user being created is an admin
     * @param array $roles          The roles that should be applied to the user being created
     * 
     * @return void
     */
    private function createUser(
        string $firstname,
        string $lastname,
        string $email,
        string $password,
        string $provider,
        bool $isAdmin,
        array $roles,
        bool $assignTeam = false
    ): void {
        $user = User::factory()->create([
            'name' => $firstname . ' ' . $lastname,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'provider' => $provider,
            'password' => Hash::make($password),
            'is_admin' => $isAdmin,
        ]);

        if ($assignTeam) {
            $teamId = Team::all()->random()->id;

            $thuId = TeamHasUser::create([
                'team_id' => $teamId,
                'user_id' => $user->id,
            ]);

            foreach ($roles as $role) {
                $r = Role::where('name', $role)->first();

                TeamUserHasRole::create([
                    'team_has_user_id' => $thuId->id,
                    'role_id' => $r->id,
                ]);
            }
        } else {
            foreach ($roles as $role) {
                $r = Role::where('name', $role)->first();
                UserHasRole::create([
                    'user_id' => $user->id,
                    'role_id' => $r->id,
                ]);
            }
        }
    }
}
