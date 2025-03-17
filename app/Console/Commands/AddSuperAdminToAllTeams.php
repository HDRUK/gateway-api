<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Team;
use App\Models\TeamHasUser;
use Illuminate\Console\Command;

class AddSuperAdminToAllTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-super-admin-to-all-teams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make sure the superadmin is a member of all teams';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teamIds = Team::pluck("id");
        $superAdminIds = User::where("is_admin", true)->pluck('id');
        foreach ($teamIds as $teamId) {
            foreach ($superAdminIds as $adminId) {
                TeamHasUser::updateOrCreate(
                    [
                        'team_id' => $teamId,
                        'user_id' => $adminId,
                    ],
                    []
                );
            }
        }
    }
}
