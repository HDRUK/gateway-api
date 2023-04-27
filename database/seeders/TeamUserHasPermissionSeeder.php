<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasPermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamUserHasPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 500; $count++) {
            $thuId = TeamHasUser::all()->random()->id;
            $permissionId = Permission::all()->random()->id;

            $tuhpUser = TeamUserHasPermission::where([
                'team_has_user_id' => $thuId,
                'permission_id' => $permissionId,
            ])->first();

            if (!$tuhpUser) {
                TeamUserHasPermission::create([
                    'team_has_user_id' => $thuId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }
}
