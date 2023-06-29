<?php

namespace Database\Seeders;

use App\Models\AppHasPermission;
use App\Models\Permission;
use App\Models\AppRegistration;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AppHasPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appIds = AppRegistration::all()->pluck('id')->toArray();
        $permissionIds = Permission::all()->pluck('id')->toArray();

        $count = 0;
        while ($count < 100) {
            $appId = $appIds[array_rand($appIds)];
            $permissionId = $permissionIds[array_rand($permissionIds)];

            $appHasPermission = AppHasPermission::where([
                'app_id' => $appId,
                'permission_id' => $permissionId,
            ])->first();

            if (!$appHasPermission) {
                AppHasPermission::create([
                    'app_id' => $appId,
                    'permission_id' => $permissionId,
                ]);
                // $count += 1;
            }
            $count += 1;
        }
    }
}
