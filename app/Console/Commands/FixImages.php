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
    protected $signature = 'app:fix-images {--dryRun : Perform a dry run without updating the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes team logos by removing MEDIA_URL prefix and optionally performs a dry run.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dryRun');
        $teams = Team::select(["id","team_logo"])->get();
        $progressbar = $this->output->createProgressBar(count($teams));
        foreach($teams as $team) {
            if(is_null($team->team_logo)) {
                $progressbar->advance();
                continue;
            }

            $fixed_team_logo = str_replace(env('MEDIA_URL'), '', $team->team_logo);
            if (!str_starts_with($fixed_team_logo, '/teams')) {
                $fixed_team_logo = null;
            }

            if ($dryRun) {
                $this->info("Team ID: {$team->id}, Old Logo: {$team->team_logo}, New Logo: {$fixed_team_logo}");
            } else {
                Team::find($team->id)->update(['team_logo' => $fixed_team_logo]);
            }
            $progressbar->advance();

        }
    }
}
