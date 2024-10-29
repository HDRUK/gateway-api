<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

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
            [
                'name' => 'hdruk.superadmin',
                'full_name' => 'HDR UK SuperAdmin',
            ],
            [
                'name' => 'hdruk.admin',
                'full_name' => 'HDR UK Admin',
            ],
            [
                'name' => 'hdruk.metadata',
                'full_name' => 'HDR UK Metadata Admin',
            ],
            [
                'name' => 'hdruk.dar',
                'full_name' => 'HDR UK - DAR Admin',
            ],
            [
                'name' => 'hdruk.custodian',
                'full_name' => 'HDR UK - Custodian Onboarding Admin',
            ],
            [
                'name' => 'hdruk.cohort.admin',
                'full_name' => 'HDR UK - Cohort Discovery Manager',
            ],
        ];

        foreach ($roles as $role) {
            Role::where([
                'name' => $role['name'],
            ])->update([
                'full_name' => $role['full_name'],
            ]);
        }
    }
}
