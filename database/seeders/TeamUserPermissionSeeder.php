<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use App\Models\TeamUserPermission;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeamUserPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 200; $count++) {
            $teamId = Team::all()->random()->id;
            $userId = User::all()->random()->id;
            $permissionId = Permission::all()->random()->id;

            $publisherUserHasPerm = TeamUserPermission::where([
                'team_id' => $teamId,
                'user_id' => $userId,
                'permission_id' => $permissionId,
            ])->first();

            if (!$publisherUserHasPerm) {
                TeamUserPermission::create([
                    'team_id' => $teamId,
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ]);
            }

            $count++;
        }
    }
}
