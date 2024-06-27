<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

class PostRunUpdateFullNameRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:post-run-update-full-name-role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run this command to update the "roles.first_name" column';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $array = [
            'custodian.team.admin' => 'TEAM ADMIN',
            'developer' => 'DEVELOPER',
            'custodian.metadata.manager' => 'METADATA MANAGER',
            'metadata.editor' => 'METADATA EDITOR',
            'custodian.dar.manager' => 'DAR MANAGER',
            'dar.reviewer' => 'DAR REVIEWER',
        ];

        $askInitFullName = $this->ask('Do you want this "roles.full_name" column to be initialized? [yes]/no', 'yes');

        if ($askInitFullName === 'yes') {
            Role::query()->update([
                'full_name' => NULL,
            ]);
        }

        $askUpdateFullName = $this->ask('Do you want the "roles.full_name" field to be updated? [yes]/no', 'yes');

        if ($askUpdateFullName === 'yes') {
            foreach ($array as $key => $value) {
                Role::where([
                    'name' => $key
                ])->update([
                    'full_name' => $value
                ]);
            }
        }

        echo 'completed update for "roles.first_name"' . PHP_EOL;
    }
}
