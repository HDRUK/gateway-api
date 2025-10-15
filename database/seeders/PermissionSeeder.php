<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'gateway' => [
                'applications.read',
                'applications.create',
                'applications.update',
                'applications.delete',

                'integrations.metadata',
                'integrations.dar',

                'datasets.read',
                'datasets.create',
                'datasets.update',
                'datasets.delete',

                'enquiries.create',
                'enquiries.read',
                'enquiries.update',
                'enquiries.delete', // Shouldn't be possible. Investigate need.

                'dar.read.all',
                'dar.read.assigned',
                'dar.update',
                'dar.decision',

                'application.read',
                'application.create',
                'application.update',
                'application.delete',

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

                // 'permissions.update',
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

                'custodians.create',
                'custodians.read',
                'custodians.update',
                'custodians.delete',

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

                'people.read',
                'people.create',
                'people.update',
                'people.delete',

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

                'notifications.update',

                'cohort.create',
                'cohort.read',
                'cohort.update',
                'cohort.delete',

                'question-bank.create',
                'question-bank.read',
                'question-bank.update',
                'question-bank.delete',

                'widgets.create',
                'widgets.read',
                'widgets.update',
                'widgets.delete',
            ],
            'cohort' => [
                'GENERAL_ACCESS',
                'SYSTEM_ADMIN',
                'BANNED',
            ],
        ];

        foreach ($permissions as $app => $perms) {
            foreach ($perms as $perm) {
                Permission::create([
                    'application' => $app,
                    'name' => $perm,
                ]);
            }
        }
    }
}
