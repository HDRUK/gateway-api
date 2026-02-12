<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleHasPermission;
use Illuminate\Database\Seeder;

class CohortRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roleName = 'hdruk.cohort.admin';
        $application = 'cohort';

        $cohortPerms = [
            'SYSTEM_ADMIN',
            'GENERAL_ACCESS',
        ];

        $role = Role::firstOrCreate(
            ['name' => $roleName],
            [
                'enabled' => true,
                'full_name' => 'HDR UK - Cohort Discovery Manager',
            ]
        );

        foreach ($cohortPerms as $permName) {
            $permName = trim($permName);

            Permission::updateOrCreate(
                [
                    'name' => $permName,
                    'application' => $application,
                ],
                [
                    'name' => $permName,
                    'application' => $application,
                ]
            );
        }

        $perms = Permission::where('application', $application)
            ->whereIn('name', $cohortPerms)
            ->get();

        foreach ($perms as $perm) {
            RoleHasPermission::firstOrCreate([
                'role_id' => $role->id,
                'permission_id' => $perm->id,
            ]);
        }
    }
}
