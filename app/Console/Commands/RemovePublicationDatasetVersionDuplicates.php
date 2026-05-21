<?php

namespace App\Console\Commands;

use App\Models\PublicationHasDatasetVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemovePublicationDatasetVersionDuplicates extends Command
{
    protected $signature = 'app:remove-publication-dataset-version-duplicates';

    protected $description = 'Remove duplicate rows from publication_has_dataset_version, keeping the lowest ID for each unique (publication_id, dataset_version_id, link_type) combination';

    public function handle(): int
    {
        $this->info('Scanning for duplicates in publication_has_dataset_version...');

        $duplicates = DB::select("
            SELECT
                MIN(id) AS keep_id,
                publication_id,
                dataset_version_id,
                link_type,
                COUNT(*) AS total
            FROM publication_has_dataset_version
            GROUP BY publication_id, dataset_version_id, link_type
            HAVING COUNT(*) > 1
        ");

        if (empty($duplicates)) {
            $this->info('No duplicates found. Table is clean.');
            return self::SUCCESS;
        }

        $groupCount  = count($duplicates);
        $deleteCount = array_sum(array_map(fn ($row) => $row->total - 1, $duplicates));

        $this->warn("Found {$groupCount} duplicate group(s) — {$deleteCount} row(s) will be deleted.");

        DB::transaction(function () use ($duplicates) {
            foreach ($duplicates as $row) {
                PublicationHasDatasetVersion::where('publication_id', $row->publication_id)
                    ->where('dataset_version_id', $row->dataset_version_id)
                    ->where('link_type', $row->link_type)
                    ->where('id', '!=', $row->keep_id)
                    ->delete();
            }
        });

        $this->info("Done. {$deleteCount} duplicate row(s) removed.");

        return self::SUCCESS;
    }
}
