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
            echo $team->id . ' ' . $team->team_logo . "\n";
        }
    }
}
