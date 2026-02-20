<?php

namespace Database\Seeders\Omop;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Exception;

class ConceptSeeder extends Seeder
{
    protected $folders = [
        'CONCEPT',
        'CONCEPT_ANCESTOR',
        'CONCEPT_RELATIONSHIP',
        'CONCEPT_SYNONYM',
    ];

    public function run()
    {
        $this->disableForeignKeyChecks();

        // Truncate the tables before seeding
        DB::table('concept')->truncate();
        DB::table('standard_concepts')->truncate();
        DB::table('concept_synonym')->truncate();
        DB::table('concept_relationship')->truncate();
        DB::table('concept_ancestor')->truncate();

        $this->enableForeignKeyChecks();

        $nChunk = config('gateway.omop_seeding_nchunks');
        $useInFileSQL = filter_var(config('gateway.omop_seeding_use_infile'), FILTER_VALIDATE_BOOLEAN);

        foreach ($this->folders as $folder) {
            $tableName = strtolower($folder);

            $folderPath = storage_path("migration_files/omop/{$folder}");

            if (!is_dir($folderPath)) {
                \Log::error("Can't find {$folderPath} - please download the TSV files and place them in this folder");
                throw new Exception("Folder not found: {$folderPath}");
            }

            $files = glob("{$folderPath}/*.tsv");

            if (!$files || count($files) === 0) {
                throw new Exception("No .tsv files found in folder: {$folderPath}");
            }

            foreach ($files as $file) {

                if ($useInFileSQL) {// use SQL command
                    $fullPath = addslashes($file);
                    DB::statement("
                        LOAD DATA LOCAL INFILE '{$fullPath}'
                        INTO TABLE {$tableName}
                        FIELDS TERMINATED BY '\t'
                        LINES TERMINATED BY '\n'
                        IGNORE 1 LINES
                    ");

                    $this->command->info("--> finished SQL load of $file");
                    continue;
                }

                if (($handle = fopen($file, 'r')) !== false) {
                    $header = fgetcsv($handle, 0, "\t");

                    $batchInsert = [];
                    while (($row = fgetcsv($handle, 0, "\t")) !== false) {
                        $rowData = array_combine($header, $row);
                        $batchInsert[] = $rowData;

                        if (count($batchInsert) === $nChunk) {
                            DB::table($tableName)->insert($batchInsert);
                            $batchInsert = [];
                        }
                    }
                    if (!empty($batchInsert)) {
                        DB::table($tableName)->insert($batchInsert);
                    }
                    unset($batchInsert);

                    fclose($handle);
                    $this->command->info("--> finished $file");
                }
            }

            $this->command->info("Data inserted successfully into {$tableName} from TSV files.");
        }
    }

    private function disableForeignKeyChecks(): void
    {
        if (!app()->environment('testing') && strtolower(DB::connection()->getDriverName()) === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }
    }

    private function enableForeignKeyChecks(): void
    {
        if (!app()->environment('testing') && strtolower(DB::connection()->getDriverName()) === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }
}
