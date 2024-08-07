<?php

namespace Database\Seeders;

use App\Models\ApplicationHasPermission;
use App\Models\Permission;
use App\Models\Application;
use Illuminate\Database\Seeder;

class ApplicationHasPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appIds = Application::all()->pluck('id')->toArray();
        $permissionIds = Permission::all()->pluck('id')->toArray();

        $count = 0;
        while ($count < 100) {
            $appId = $appIds[array_rand($appIds)];
            $permissionId = $permissionIds[array_rand($permissionIds)];

            $appHasPermission = ApplicationHasPermission::where([
                'application_id' => $appId,
                'permission_id' => $permissionId,
            ])->first();

            if (!$appHasPermission) {
                ApplicationHasPermission::create([
                    'application_id' => $appId,
                    'permission_id' => $permissionId,
                ]);
                // $count += 1;
            }
            $count += 1;
        }
    }
}
