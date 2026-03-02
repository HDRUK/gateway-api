<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RegeneratePidForTeam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:regenerate-pid-for-team {teamId : The ID of the team}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command regenerate PIDs for all active datasets of a team';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teamId = $this->argument('teamId');

        if (!is_numeric($teamId) || (int) $teamId <= 0) {
            $this->error('Invalid team ID provided.');
            return Command::FAILURE;
        }

        $teamId = (int) $teamId;

        $datasets = Dataset::where([
            'team_id' => $teamId,
            'status' => Dataset::STATUS_ACTIVE,
            ])->get();

        foreach ($datasets as $dataset) {
            $newPid = (string) Str::uuid();
            $dataset->pid = $newPid;
            $dataset->save();

            $this->info("Updated dataset ID {$dataset->id} with new PID: {$newPid}");

            $latestDatasetVersion = DatasetVersion::where('dataset_id', $dataset->id)->latest('version')->select(['id'])->first();

            $this->info("Latest dataset version ID for dataset ID {$dataset->id}: {$latestDatasetVersion->id}");

            \DB::statement("
                UPDATE dataset_versions 
                SET metadata = JSON_SET(metadata, '$.metadata.required.gatewayPid', CAST(? AS CHAR))
                WHERE id = ? 
                LIMIT 1
            ", [$newPid, $latestDatasetVersion->id]);

            \DB::statement("
                UPDATE dataset_versions 
                SET metadata = JSON_SET(metadata, '$.original_metadata.identifier', CAST(? AS CHAR))
                WHERE id = ? 
                LIMIT 1
            ", [$newPid, $latestDatasetVersion->id]);
        }
    }
}
