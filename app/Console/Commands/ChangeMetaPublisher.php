<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Dataset;
use Illuminate\Console\Command;

class ChangeMetaPublisher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-meta-publisher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will change the metadata.metadata.summary.publisher.name and the metadata.metadata.summary.publisher.gatewayId to what is provided';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // These are the dataset IDs we want to change
        $items = [
            989,
            986,
            979,
            977,
            962,
            960,
            959,
            958,
            956,
            953,
            952,
            950,
            921,
            919,
            917,
        ];

        // Target Publisher ID
        $targetPublisherId = 99;


        $team = Team::where('name', 'like', '%' . $targetPublisherId . '%')->first();

        if ($team) {
            $this->info($team->name . ' is what will be used for Publisher Name');

            // Loop through each dataset
            foreach ($items as $item) {
                $dataset = Dataset::where('id', $item)->first();

                if ($dataset) {
                      $latestVersion = $dataset->latestVersion();

                    if ($latestVersion) {
                        $metadata = json_decode($latestVersion->metadata, true);

                        if (isset($metadata['metadata']['summary']['publisher'])) {
                            $metadata['metadata']['summary']['publisher']['name'] = $team->name;
                            $metadata['metadata']['summary']['publisher']['gatewayId'] = $targetPublisherId;
                            $latestVersion->metadata = $metadata;
                            $latestVersion->save();

                            $this->info('Metadata updated for Dataset ID ' . $dataset->id);
                        } else {
                            $this->warn('Publisher field not found in metadata for Dataset ID ' . $dataset->id);
                        }
                    } else {
                        $this->warn('Latest version not found for Dataset ID ' . $dataset->id);
                    }
                } else {
                    $this->warn('Dataset not found for ID ' . $item);
                }
            }
        } else {
            $this->warn('Team not found for Publisher ID ' . $targetPublisherId);
        }
    }
}
