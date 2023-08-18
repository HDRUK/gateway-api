<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeamUserHasRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 100; $count++) {
            $thuId = TeamHasUser::where('user_id', '<>', 1)->get()->random()->id;
            $roleId = Role::all()->random()->id;

            $tuhrUser = TeamUserHasRole::where([
                'team_has_user_id' => $thuId,
                'role_id' => $roleId,
            ])->first();

            if (!$tuhrUser) {
                TeamUserHasRole::create([
                    'team_has_user_id' => $thuId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }
}
