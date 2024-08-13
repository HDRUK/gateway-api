<?php

namespace Database\Seeders\Traits;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\UserHasRole;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;

trait HelperFunctions
{
    /**
     * Generically creates users per passed params
     *
     * @param string $firstname     The firstname of the user to create
     * @param string $lastname      The lastname of the user to create
     * @param string $email         The email address of the user to create
     * @param string $password      The password of the user to create
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
        bool $isAdmin,
        array $roles,
        bool $assignTeam = false
    ): void {
        $user = User::where('email', $email)->first();
        if ($user) {
            return;
        }

        $user = User::factory()->create([
            'name' => $firstname . ' ' . $lastname,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'provider' => 'service',
            'password' => $password,
            'is_admin' => $isAdmin,
        ]);

        if ($assignTeam && Team::count() > 0) {
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
