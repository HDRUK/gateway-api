<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\RoleHasPermission;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'hdruk.superadmin' => [
                'permissions' => [
                    'perms' => 'all',
                ]
            ],
            'hdruk.admin' => [
                'permissions' => [
                    'datasets.read',
                    'datasets.delete',
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
            ],
            'hdruk.metadata' => [
                'permissions' => [
                    'integrations.metadata',
                    'datasets.read',
                    'datasets.create',
                    'datasets.update',
                    'datasets.delete',
                ],
            ],
            'hdruk.dar' => [
                'permissions' => [
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
                    'team-members.update',
                    'roles.mdm.update',
                    'roles.mde.update',
                ],
            ],
            'hdruk.custodian' => [
                'permissions' => [
                    'datasets.read',
                    'custodians.create',
                    'roles.cta.update',
                    'roles.dev.update',
                    'roles.mdm.update',
                    'roles.mde.update',
                    'roles.dar-m.update',
                    'roles.dar-r.update',
                    'team-members.create',
                    'team-members.update',
                    'team-members.delete',
                ],
            ],
            'hdruk.cohort.admin' => [
                'permissions' => [
                    'cohort.create',
                    'cohort.read',
                    'cohort.update',
                    'cohort.delete',
                ],
            ],
            'custodian.team.admin' => [
                'full_name' => 'TEAM ADMIN',
                'permissions' => [
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
                    'roles.read',
                    'roles.cta.update',
                    'roles.dev.update',
                    'roles.mdm.update',
                    'roles.mde.update',
                    'roles.dar-m.update',
                    'roles.dar-r.update',
                    'team-members.create',
                    'team-members.update',
                    'team-members.delete',
                    'custodians.update',
                    'collections.read',
                    'notifications.update',
                ],
            ],
            'developer' => [
                'full_name' => 'DEVELOPER',
                'permissions' => [
                    'applications.read',
                    'applications.create',
                    'applications.update',
                    'applications.delete',
                    'integrations.metadata',
                    'integrations.dar',
                    'datasets.read',
                ],
            ],
            'custodian.metadata.manager' => [
                'full_name' => 'METADATA MANAGER',
                'permissions' => [
                    'datasets.read',
                    'datasets.create',
                    'datasets.update',
                    'datasets.delete',
                    'roles.read',
                    'roles.mdm.update',
                    'roles.mde.update',
                    'team-members.create',
                    'team-members.update',
                ],
            ],
            'metadata.editor' => [
                'full_name' => 'METADATA EDITOR',
                'permissions' => [
                    'datasets.read',
                    'datasets.create',
                    'datasets.update',
                    'roles.read',
                    'team-members.update',
                    'roles.mde.update',
                ],
            ],
            'custodian.dar.manager' => [
                'full_name' => 'DAR MANAGER',
                'permissions' => [
                    'integrations.dar',
                    'datasets.read',
                    'enquiries.create',
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
                    'roles.read',
                    'team-members.update',
                    'roles.dar-m.update',
                    'roles.dar-r.update',
                    'team-members.create',               
                ],
            ],
            'dar.reviewer' => [
                'full_name' => 'REVIEWER',
                'permissions' => [
                    'datasets.read',
                    'dar.read.assigned',
                    'dar.update', 
                    'roles.read',
                    'team-members.update',
                    'roles.dar-r.update',
                ],
            ],
        ];

        foreach ($roles as $k => $v) {
            $role = Role::create([
                'name' => $k,
                'enabled' => true,
                'full_name' => array_key_exists('full_name', $v) ? $v['full_name'] : NULL,
            ]);

            foreach($v['permissions'] as $p) {
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
