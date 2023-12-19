<?php

namespace Database\Seeders\Demo;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use App\Models\RoleHasPermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'hdruk.superadmin' => [
                'perms' => 'all',
            ],
            'hdruk.admin' => [
                'datasets.read',
                'datasets.delete',
                'permissions.update',
                'custodians.create',
                'custodians.read',
                'custodians.update',
                'custodians.delete',
                'tools.read',
                'tools.create',
                'tools.update',
                'tools.delete',
                'filters.read',
                'filters.create',
                'filters.update',
                'filters.delete',
                'features.read',
                'features.create',
                'features.update',
                'features.delete',
                'sectors.read',
                'sectors.create',
                'sectors.update',
                'sectors.delete',
                'audit.read',
                'audit.create',
                'audit.update',
                'audit.delete',
            ],
            'hdruk.metadata' => [
                'integrations.metadata',
                'datasets.read',
                'datasets.create',
                'datasets.update',
                'datasets.delete',
                'permissions.update',
            ],
            'hdruk.dar' => [
                'integrations.dar',
                'datasets.read',
                'enquiries.read',
                'dar.read.assigned',
                'dar.decision',
                'workflows.update',
                'dar-form.update',
                'dur.read',
                'dur.create',
                'dur.update',
                'dur.delete',
                'permissions.update',
            ],
            'hdruk.custodian' => [
                'datasets.read',
                'permissions.update',
                'custodians.create',
            ],
            'hdruk.cohort.admin' => [
                'cohort.create',
                'cohort.read',
                'cohort.update',
                'cohort.delete',
            ],
            'custodian.team.admin' => [
                'applications.read',
                'applications.create',
                'applications.update',
                'applications.delete',
                'integrations.metadata',
                'integrations.dar',
                'datasets.read',
                'enquiries.read',
                'dar.read.all',
                'workflows.read',
                'dar-config.update',
                'dar-form.read',
                'permissions.update',
                'custodians.update',
                'collections.read',
                'notifications.update',
            ],
            'developer' => [
                'applications.read',
                'applications.create',
                'applications.update',
                'applications.delete',
                'integrations.metadata',
                'integrations.dar',
                'datasets.read',
            ],
            'custodian.metadata.manager' => [
                'integrations.metadata',
                'datasets.read',
                'datasets.create',
                'datasets.update',
                'datasets.delete',
                'permissions.update',
            ],
            'metadata.editor' => [
                'datasets.read',
                'datasets.create',
                'datasets.update',
            ],
            'metadata.manager' => [
                'datasets.read',
                'datasets.create',
                'datasets.update',
                'datasets.delete',
                'permissions.update',
            ],
            'custodian.dar.manager' => [
                'integrations.dar',
                'datasets.read',
                'enquiries.create',
                'enquiries.read',
                'dar.read.all',
                'dar.read.assigned',
                'dar.update',
                'dar.decision',
                'workflows.read',
                'workflows.create',
                'workflows.update',
                'workflows.delete',
                'dar-config.update',
                'dar-form.create',
                'dar-form.read',
                'dar-form.update',
                'permissions.update',
            ],
            'dar.reviewer' => [
                'datasets.read',
                'dar.read.assigned',
                'dar.update',
            ],
            'dar.manager' => [
                'datasets.read',
                'enquiries.read',
                'enquiries.update',
                'dar.read.all',
                'dar.read.assigned',
                'dar.update',
                'dar.decision',
                'workflows.read',
                'workflows.create',
                'workflows.update',
                'workflows.delete',
                'workflow.assign',
                'dar-config.update',
                'dar-form.create',
                'dar-form.read',
                'dar-form.update',
                'dur.read',
                'dur.create',
                'dur.update',
                'dur.delete',
                'permissions.update',
            ],
        ];

        foreach ($roles as $k => $v) {
            $role = Role::create([
                'name' => $k,
                'enabled' => true,
            ]);

            foreach ($v as $p) {
                $perm = null;

                if ($p !== 'all') {
                    $perm = Permission::where([
                        'name' => $p,
                        'application' => 'gateway',
                    ])->first();

                    RoleHasPermission::create([
                        'role_id' => $role->id,
                        'permission_id' => $perm->id,
                    ]);
                } else {
                    $perm = Permission::where([
                        'application' => 'gateway',
                    ])->get();
                    foreach ($perm as $p) {
                        RoleHasPermission::create([
                            'role_id' => $role->id,
                            'permission_id' => $p->id,
                        ]);
                    }
                }
            }
        }
    }
}
