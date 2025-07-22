<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;

use Illuminate\Console\Command;

class CompareGAT7330 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:compare-gat-7330 {pre : The full file location of the json file from the precheck command.} {post : The full file location of the json file from the postcheck command.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run this command to compare the json values from files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $precheckFile = $this->argument('pre');
        $postcheckFile = $this->argument('post');

        $string = file_get_contents($precheckFile);
        $json_pre = json_decode($string);

        $string = file_get_contents($postcheckFile);
        $json_post = json_decode($string);

        unset($string);

        echo "type | id | team_id | user_id | \n";
        foreach (array_slice($json_pre, 0, count($json_pre)) as $entity) {
            $teamId = "";
            if ($entity->type === "DatasetVersion") {
                $dv = DatasetVersion::where('id', $entity->id)->select('dataset_id')->first();
                if ($dv) {
                    $dataset = Dataset::where('id', $dv->dataset_id)->first();
                    if ($dataset) {
                        $teamId = $dataset->team_id;
                    }
                }
            } else {
                $teamId = $entity->team_id ?? "";
            }

            $entityResultString = $entity->type . " | " . $entity->id . " | " . $teamId . " | " . ($entity->user_id ?? "") . " | ";
            $difference = false;
            $match = array_filter($json_post, function ($val) use ($entity) {
                return (($val->type === $entity->type) && ($val->id === $entity->id));
            });
            if ($match) {
                $match = $match[array_key_first($match)];
                foreach (['DatasetVersion', 'Dur', 'Collection', 'Publication', 'Tool'] as $entityType) {
                    if ($match->$entityType !== $entity->$entityType) {
                        $matchArray = json_decode($match->$entityType);
                        $entityArray = json_decode($entity->$entityType);
                        $missing = array_diff($entityArray ?? [], $matchArray ?? []);
                        $added = array_diff($matchArray ?? [], $entityArray ?? []);
                        if ($missing) {
                            $entityResultString .= $entityType . " missing: " . json_encode(array_values($missing)) . " | ";
                            $difference = true;
                        }
                        if ($added) {
                            $entityResultString .= $entityType . " added: " . json_encode(array_values($added)) . " | ";
                            $difference = true;
                        }
                    }
                }
            }
            if ($difference) {
                echo $entityResultString . "\n";
            }
        }

        return;
    }
}
