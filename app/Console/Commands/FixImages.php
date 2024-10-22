<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;

class FixImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-images';

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
        $teams = Team::select(["id","team_logo"])->get();
        foreach($teams as $team) {
            if(is_null($team->team_logo)) {
                continue;
            }

            $fixed_team_logo = str_replace(env('MEDIA_URL'), '', $team->team_logo);
            if (!str_starts_with($fixed_team_logo, '/teams')) {
                $fixed_team_logo = null;
            }

            Team::find($team->id)->update(['team_logo' => $fixed_team_logo]);

        }
    }
}
