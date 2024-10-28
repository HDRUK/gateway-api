<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Models\Collection;

class AssignTeamToCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-team-to-collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a team to own a collection based on a csv file';

    private $csvData = [];

    public function __construct()
    {
        parent::__construct();
        $this->readMigrationFile(storage_path() . '/migration_files/mapped_collections_team_id.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $csvData = $this->csvData;
        $fallbackTeam = Team::where("name", "like", "%Health Data Research UK%")->select("id")->first();

        $progressbar = $this->output->createProgressBar(count($csvData));
        $progressbar->start();

        foreach ($csvData as $item) {
            $collectionMongoId = $item['collection_id'];
            $teamMongoId = $item['team_id'];
            $teamName = $item['team_name'];
            $team = Team::where("mongo_object_id", $teamMongoId)->select("id")->first();
            if(!$team && !is_null($teamName)) {
                $team = Team::where("name", "like", "%".$teamName."%")->select("id")->first();
            }
            if(!$team) {
                $team = $fallbackTeam;
            }

            Collection::where("mongo_object_id", $collectionMongoId)->update(['team_id' => $team->id]);
            $progressbar->advance();
        }
    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[trim($headers[$key], "\xEF\xBB\xBF")] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);
    }
}
