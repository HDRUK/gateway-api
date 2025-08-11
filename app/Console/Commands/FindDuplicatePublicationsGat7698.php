<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use Illuminate\Console\Command;

class FindDuplicatePublicationsGat7698 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:find-duplicate-publications-gat7698';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pubs = json_decode(
            DatasetVersion::where('dataset_id', 1378)
                ->selectRaw(
                    "JSON_EXTRACT(JSON_UNQUOTE(metadata),'$.metadata.linkage.publicationUsingDataset') as pubs"
                )
                ->first()->pubs,
            true
        );
        dump(count($pubs));
    }
}
