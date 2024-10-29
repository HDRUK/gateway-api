<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Console\Command;
use App\Models\RoleHasPermission;

class UpdateRoleNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-role-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roles = [
            'hdruk.superadmin' => [
                'full_name' => 'HDR UK SuperAdmin',
                'permissions' => [
                    'perms' => 'all',
                ]
            ],
            'hdruk.admin' => [
                'full_name' => 'HDR UK Admin',
                'permissions' => [
                    'datasets.read',
                    'datasets.delete',
                    'question-bank.create',
                    'question-bank.read',
                    'question-bank.update',
                    'question-bank.delete',
                    'permissions.update',
                    'custodians.create',
                    'custodians.read',
                    'custodians.update',
                    'custodians.delete',
                    'tools.read',
                    'tools.create',
                    'tools.update',
                    'tools.delete',
                    'theme.read',
                    'theme.create',
                    'theme.update',
                    'theme.delete',
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
                'full_name' => 'HDR UK Metadata Admin',
                'permissions' => [
                    'integrations.metadata',
                    'datasets.read',
                    'datasets.create',
                    'datasets.update',
                    'datasets.delete',
                ],
            ],
            'hdruk.dar' => [
                'full_name' => 'HDR UK - DAR Admin',
                'permissions' => [
                    'integrations.dar',
                    'datasets.read',
                    'enquiries.read',
                    'question-bank.create',
                    'question-bank.read',
                    'question-bank.update',
                    'question-bank.delete',
                    'dar-form.read',
                    'dar-form.update',
                    'dur.read',
                    'dur.create',
                    'dur.update',
                    'dur.delete',
                    'permissions.update',
                    'roles.mdm.update',
                    'roles.mde.update',
                    'team-members.update',
                ],
            ],
            'hdruk.custodian' => [
                'full_name' => 'HDR UK - Custodian Onboarding Admin',
                'permissions' => [
                    'datasets.read',
                    'permissions.update',
                    'roles.cta.update',
                    'roles.dev.update',
                    'roles.mdm.update',
                    'roles.mde.update',
                    'roles.dar-m.update',
                    'roles.dar-r.update',
                    'team-members.create',
                    'team-members.update',
                    'team-members.delete',

                    'custodians.create',
                    'custodians.read',
                    'custodians.update',
                    'custodians.delete',
                ],
            ],
            'hdruk.cohort.admin' => [
                'full_name' => 'HDR UK - Cohort Discovery Manager',
                'permissions' => [
                    'cohort.create',
                    'cohort.read',
                    'cohort.update',
                    'cohort.delete',
                ],
            ],
            'custodian.team.admin' => [
                'full_name' => 'Team Admin',
                'permissions' => [
                    'applications.read',
                    'applications.create',
                    'applications.update',
                    'applications.delete',
                    'integrations.metadata',
                    'integrations.dar',
                    'datasets.read',
                    'enquiries.read',
                    'question-bank.read',
                    'data-access-template.read',
                    'data-access-applications.provider.read',
                    'workflows.read',
                    'dar-config.update',
                    'dar-form.read',
                    'dur.read',
                    'permissions.update',
                    'notifications.read',
                    'notifications.update',
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
                    'papers.read',
                    'papers.create',
                    'papers.update',
                    'papers.delete',
                    'tools.read',
                    'tools.create',
                    'tools.update',
                    'tools.delete',
                    'collections.read',
                    'collections.create',
                    'collections.update',
                    'collections.delete',
                ],
            ],
            'developer' => [
                'full_name' => 'Developer',
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
                'full_name' => 'Metadata Manager',
                'permissions' => [
                    'datasets.read',
                    'datasets.create',
                    'datasets.update',
                    'datasets.delete',
                    'permissions.update',
                    'roles.read',
                    'roles.mdm.update',
                    'roles.mde.update',
                    'team-members.create',
                    'team-members.update',
                    'papers.read',
                    'papers.create',
                    'papers.update',
                    'papers.delete',
                    'tools.read',
                    'tools.create',
                    'tools.update',
                    'tools.delete',
                ],
            ],
            'metadata.editor' => [
                'full_name' => 'Metadata Editor',
                'permissions' => [
                    'datasets.read',
                    'datasets.create',
                    'datasets.update',
                    'roles.read',
                ],
            ],
            'custodian.dar.manager' => [
                'full_name' => 'DAR Manager',
                'permissions' => [
                    'datasets.read',
                    'enquiries.read',
                    'enquiries.update',
                    'question-bank.read',
                    'data-access-template.read',
                    'data-access-template.create',
                    'data-access-template.update',
                    'data-access-template.delete',
                    'data-access-applications.provider.read',
                    'data-access-applications.provider.update',
                    'data-access-applications.decision.update',
                    'data-access-applications.review.read',
                    'data-access-applications.review.create',
                    'data-access-applications.review.update',
                    'data-access-applications.status.read',
                    'data-access-applications.status.create',
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
                    'roles.read',
                    'roles.dar-m.update',
                    'roles.dar-r.update',
                    'team-members.update',
                    'team-members.create',
                    'papers.read',
                    'papers.create',
                    'papers.update',
                    'papers.delete',
                    'tools.read',
                    'tools.create',
                    'tools.update',
                    'tools.delete',
                ],
            ],
            'dar.reviewer' => [
                'full_name' => 'DAR Reviewer',
                'permissions' => [
                    'datasets.read',
                    'question-bank.read',
                    'data-access-template.read',
                    'data-access-applications.provider.read',
                    'data-access-applications.review.read',
                    'data-access-applications.review.create',
                    'data-access-applications.review.update',
                    'data-access-applications.status.read',
                    'data-access-applications.status.create',
                    'roles.read',
                ],
            ],
        ];

        $otherPerms = [
            [
                'name' => 'GENERAL_ACCESS',
                'application' => 'cohort',
            ],
            [
                'name' => 'SYSTEM_ADMIN',
                'application' => 'cohort',
            ],
            [
                'name' => 'BANNED',
                'application' => 'cohort',
            ],
        ];

        $perms = [];
        foreach ($roles as $k => $v) {
            if ($k === 'hdruk.superadmin') {
                continue;
            }

            $perms = array_unique(array_merge($perms, $v['permissions']));
        }

        foreach ($perms as $perm) {
            Permission::updateOrCreate([
                'name' => trim($perm),
            ],
                [
                'name' => trim($perm),
                'application' => 'gateway'
            ]
            );
        }

        foreach ($roles as $k => $v) {
            $role = Role::updateOrCreate(
                [
                'name' => $k,
            ],
                [
                'name' => $k,
                'enabled' => true,
                'full_name' => 'full_name',
            ]);

            RoleHasPermission::where([
                'role_id' => $role->id,
            ])->delete();

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

        foreach ($otherPerms as $otherPerm) {
            Permission::updateOrCreate(
                [
                'name' => trim($otherPerm['name']),
            ],
                [
                'name' => trim($otherPerm['name']),
                'application' => trim($otherPerm['application'])
            ]);
        }
    }
}
