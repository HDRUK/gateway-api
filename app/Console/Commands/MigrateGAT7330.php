<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CollectionHasDatasetVersion;
use App\Models\CollectionHasDur;
use App\Models\CollectionHasPublication;
use App\Models\CollectionHasTool;
use App\Models\DatasetVersionHasTool;
use App\Models\DurHasDatasetVersion;
use App\Models\DurHasPublication;
use App\Models\DurHasTool;
use App\Models\PublicationHasDatasetVersion;
use App\Models\PublicationHasTool;
use App\Models\Collection;
use App\Models\DatasetVersion;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Publication;
use App\Models\Tool;

class MigrateGAT7330 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-archiving-behaviour-gat-7330';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run this command to migrate data entities to use the "new" archiving/deletion behaviour';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // For relationships, if deleted then remove. If not deleted, then keep.
        // Most have a deleted_at.

        /** CollectionHasX */
        CollectionHasDatasetVersion::onlyTrashed()->forceDelete();
        CollectionHasDur::onlyTrashed()->forceDelete();
        CollectionHasPublication::onlyTrashed()->forceDelete();
        CollectionHasTool::onlyTrashed()->forceDelete();

        /** DatasetVersionHasX */
        DatasetVersionHasTool::onlyTrashed()->forceDelete();

        /** DurHasX */
        DurHasDatasetVersion::onlyTrashed()->forceDelete();
        DurHasPublication::onlyTrashed()->forceDelete();
        DurHasTool::onlyTrashed()->forceDelete();

        /** PublicationHasX */
        PublicationHasDatasetVersion::onlyTrashed()->forceDelete();
        PublicationHasTool::onlyTrashed()->forceDelete();


        // For entities, if archived and soft-deleted, then move to only archived
        // If archived and not soft-deleted, keep as-is.
        // If soft-deleted but not archived, then move to archived and stay deleted.
        Collection::onlyTrashed()->where('status', 'ARCHIVED')->restore();
        Collection::onlyTrashed()->where('status', '!=', 'ARCHIVED')->update(['status' => 'ARCHIVED']);

        // DatasetVersions: restore only the latest version of a given dataset if that version has been deleted.
        // We thus keep old versions deleted, but DatasetVersions are moved to the new archiving behaviour.
        $datasets = Dataset::withTrashed()->get();
        foreach ($datasets as $dataset) {
            $latestDatasetVersion = DatasetVersion::withTrashed()->where('dataset_id', $dataset->id)->orderBy('version', 'desc')->first();
            if ($latestDatasetVersion) {
                $latestDatasetVersion->restore();
            }
        }
        Dataset::onlyTrashed()->where('status', 'ARCHIVED')->restore();
        Dataset::onlyTrashed()->where('status', '!=', 'ARCHIVED')->update(['status' => 'ARCHIVED']);

        Dur::onlyTrashed()->where('status', 'ARCHIVED')->restore();
        Dur::onlyTrashed()->where('status', '!=', 'ARCHIVED')->update(['status' => 'ARCHIVED']);

        Publication::onlyTrashed()->where('status', 'ARCHIVED')->restore();
        Publication::onlyTrashed()->where('status', '!=', 'ARCHIVED')->update(['status' => 'ARCHIVED']);

        Tool::onlyTrashed()->where('status', 'ARCHIVED')->restore();
        Tool::onlyTrashed()->where('status', '!=', 'ARCHIVED')->update(['status' => 'ARCHIVED']);
    }
}
