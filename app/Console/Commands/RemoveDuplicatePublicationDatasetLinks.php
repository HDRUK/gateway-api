<?php

namespace App\Console\Commands;

use App\Models\PublicationHasDatasetVersion;
use Illuminate\Console\Command;

class RemoveDuplicatePublicationDatasetLinks extends Command
{
    protected $signature = 'app:remove-duplicate-publication-dataset-links';
    protected $description = 'Remove duplicate rows from publication_dataset_version table based on publication_id, dataset_version_id, and link_type';

    public function handle(): int
    {
        $duplicates = \DB::table('publication_has_dataset_version')
            ->select('publication_id', 'dataset_version_id', 'link_type', \DB::raw('MIN(id) as keep_id'))
            ->groupBy('publication_id', 'dataset_version_id', 'link_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicates found.');
            return self::SUCCESS;
        }

        $this->info("Found {$duplicates->count()} duplicate groups. Removing...");

        $deleted = 0;

        foreach ($duplicates as $duplicate) {
            $deleted += PublicationHasDatasetVersion::query()
                ->where('publication_id', $duplicate->publication_id)
                ->where('dataset_version_id', $duplicate->dataset_version_id)
                ->where('link_type', $duplicate->link_type)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        $this->info("Removed {$deleted} duplicate rows.");

        return self::SUCCESS;
    }
}
