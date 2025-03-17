<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Tool;
use App\Models\License;
use App\Models\TypeCategory;
use Illuminate\Console\Command;
use App\Models\ProgrammingLanguage;
use App\Models\ToolHasTypeCategory;
use App\Models\ToolHasProgrammingLanguage;
use App\Http\Traits\IndexElastic;

class ToolsPostMigrationProcess extends Command
{
    use IndexElastic;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tools-post-migration-process';

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
    protected $description = 'CLI command to post-process migrated tools from mk1 mongo db.
        Used to re-align and align new data types to existing tools for increased functionality.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $this->readMigrationFile(storage_path() . '/migration_files/tool_metadata_export_mapping.csv');
        $this->readMigrationFile(storage_path() . '/migration_files/tool_metadata_export_mapping_IOv2.csv');

        // Traverse the CSV data and update migrations accordingly
        foreach ($this->csvData as $csv) {
            try {
                $tool = Tool::where('mongo_object_id', $csv['_id'])->first();
                if ($tool) {
                    /**
                     * Step 1. Removal of existing links
                     */
                    // Remove existing linked category types
                    $existingCategories = ToolHasTypeCategory::where('tool_id', $tool->id)->get();
                    if ($existingCategories) {
                        foreach ($existingCategories as $cat) {
                            if ($cat->delete()) {
                                echo 'deleted existing type category ' . $cat->id . ' for tool ' . $tool->id . "\n";
                            }
                        }
                    }

                    // Remove existing linked programming languages
                    $existingLanguages = ToolHasProgrammingLanguage::where('tool_id', $tool->id)->get();
                    if ($existingLanguages) {
                        foreach ($existingLanguages as $lang) {
                            if ($lang->delete()) {
                                echo 'deleted existing programming language ' . $lang->id . ' for tool ' . $tool->id . "\n";
                            }
                        }
                    }

                    /**
                     * Step 2. Update migrated records with data from post-migration curation data
                     */

                    // Set the programming language
                    $programmingLangFromCsv = explode(',', $csv['MK2 Programming Language (array)']);
                    foreach ($programmingLangFromCsv as &$prog) {
                        $prog = trim($prog);
                    }

                    $programmingLanguages = ProgrammingLanguage::whereIn('name', $programmingLangFromCsv)->get();
                    foreach ($programmingLanguages as $prog) {
                        echo 'creating ToolHasProgrammingLanguage for tool ' . $tool->id . ' and programming language ' . $prog->id . "\n";
                        $lang = ToolHasProgrammingLanguage::create([
                            'tool_id' => $tool->id,
                            'programming_language_id' => $prog->id,
                        ]);
                    }

                    // Add new categories
                    $categoriesFromCsv = explode(',', $csv['MK2 Type_Category (array)']);
                    foreach ($categoriesFromCsv as &$cat) {
                        $cat = trim($cat); // Normalise and remove extra whitespace
                    }

                    $categories = TypeCategory::whereIn('name', $categoriesFromCsv)->get();

                    foreach ($categories as $category) {
                        echo 'creating ToolHasTypeCategory link for tool ' . $tool->id . ' and category type ' . $category->id . "\n";
                        $type = ToolHasTypeCategory::create([
                            'tool_id' => $tool->id,
                            'type_category_id' => $category->id,
                        ]);
                    }

                    /**
                     * Final Step. Set license type for migrated tool and save record
                     */
                    $licenceId = null;
                    if ($csv['license'] !== '') {
                        $licences = License::where(['code' => trim($csv['license'])])->first();
                        if (!is_null($licences)) {
                            $licenceId = $licences->id;
                        }
                    }
                    $tool->license = $licenceId;
                    $tool->save();

                    $this->indexElasticTools($tool->id);

                    echo 'completed post-process of migration for tool ' . $tool->id . PHP_EOL;
                } else {
                    echo 'no tool matching ' . $csv['_id'] . ' ignoring'  . PHP_EOL;
                }
            } catch (Exception $e) {
                echo 'unable to process ' . $csv['_id'] . ' because ' . $e->getMessage() . "\n";
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

    /**
     * Get the CSV data for testing purposes.
     *
     * @return array
     */
    public function getCsvData(): array
    {
        return $this->csvData;
    }

}
