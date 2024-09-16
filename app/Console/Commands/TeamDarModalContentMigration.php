<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;

class TeamDarModalContentMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:team-dar-modal-content';

    /**
     * The file of migration mappings translated to CSV array
     *
     * @var array
     */
    private $csvData = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to data access modal content to teams';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->readMigrationFile(storage_path() . '/migration_files/team_dar_modal_content.csv');

        foreach ($this->csvData as $csv) {
            $team = Team::where('mongo_object_id', $csv['_id'])->first();
            if ($team) {
                $team->update([
                    'dar_modal_content' => $csv['body']
                ]);
            }
        }
    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[$headers[$key]] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);
    }

}
