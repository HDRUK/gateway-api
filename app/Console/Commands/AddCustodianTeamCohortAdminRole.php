<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

class AddCustodianTeamCohortAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-custodian-team-cohort-admin-role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add custodian.team.cohortAdmin role to roles table';

    private $csvData = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Role::create([
            'name' => 'custodian.team.cohortAdmin',

            'full_name' => 'Team Cohort Discovery Admin',
            'enabled' => 1,
        ]);
    }
}
