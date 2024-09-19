<?php

namespace Database\Seeders\Omop;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // Truncate the tables before seeding
        DB::table('concept')->truncate();
        DB::table('standard_concepts')->truncate();
        DB::table('concept_synonym')->truncate();
        DB::table('concept_relationship')->truncate();
        DB::table('concept_ancestor')->truncate();

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $nChunk = env('OMOP_SEEDING_NCHUNKS', 1000);
        $useInFileSQL = filter_var(env('OMOP_SEEDING_USE_INFILE', false), FILTER_VALIDATE_BOOLEAN);

        foreach ($this->folders as $folder) {
            $tableName = strtolower($folder);
            $files = glob(storage_path("migration_files/omop/{$folder}/*.tsv"));

            foreach ($files as $file) {

                if($useInFileSQL) {// use SQL command
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
}
