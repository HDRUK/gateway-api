<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;

class PostMigrationPublishers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:post-migration-publishers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $csvData = [];

    public function __construct()
    {
        parent::__construct();
        $this->readMigrationFile(storage_path() . '/migration_files/prod_publishers.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $csvData = $this->csvData;

        foreach ($csvData as $item) {
            $teamMongoId = trim($item['_id']);
            $teamImgUrl = trim($item['imageURL']);
            $teamIntroduction = htmlentities(trim($item['Introduction']));

            Team::where([
                'mongo_object_id' => trim($teamMongoId),
            ])->update([
                'team_logo' => trim($teamImgUrl),
                'introduction' => trim($teamIntroduction),
            ]);
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
