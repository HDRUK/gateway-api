<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
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
        $datasetId = 1378;
        $datasetVersion = DatasetVersion::where('dataset_id' . $datasetId);
        $datasetVersionId = $datasetVersion->first()->id;

        dump('datasetId=', $datasetId);
        dump('datasetVersionId=', $datasetVersionId);

        $pubs = json_decode(
            $datasetVersion
                ->selectRaw(
                    "JSON_EXTRACT(JSON_UNQUOTE(metadata),'$.metadata.linkage.publicationUsingDataset') as pubs"
                )
                ->first()->pubs,
            true
        );
        dump('number of publications in metadata=' . count($pubs));

        $normalizedPubs = $pubs;

        $publications = Publication::select('id', 'paper_doi')
            ->where(function ($q) use ($normalizedPubs) {
                foreach ($normalizedPubs as $doi) {
                    $q->orWhere('paper_doi', 'like', "%{$doi}%");
                }
            })->get();

        dump('number of publications (from metadata) in publication table=', count($publications));

        $duplicates = $publications
            ->groupBy('paper_doi')
            ->filter(function ($group) {
                return $group->count() > 1;
            });

        dump('number of publication (from metadata) that have be duplicated:', $duplicates->count());
        //dump($duplicates->toArray());


        $publications = Publication::selectRaw("
            id,
            REPLACE(REPLACE(paper_doi, 'https://doi.org/', ''), 'http://doi.org/', '') as clean_doi
        ")
            ->where(function ($q) use ($normalizedPubs) {
                foreach ($normalizedPubs as $doi) {
                    $q->orWhere('paper_doi', 'like', "%{$doi}%");
                }
            })
            ->get();

        //dump(count($publications));

        $duplicates = $publications
            ->groupBy('clean_doi')
            ->filter(fn($group) => $group->count() > 1);

        //dump($duplicates->count());
        //dump($duplicates->toArray());

        $nLinks = PublicationHasDatasetVersion::where('dataset_version_id', $datasetVersionId)->count();
        dump('number of existing links for this dataset=' . $nLinks);

        $nLinks = PublicationHasDatasetVersion::where('dataset_version_id', $datasetVersionId)
            ->whereNotIn('publication_id' . $publications->pluck('id'))
            ->count();

        dump('number of existing links for this dataset, not in the metadata=' . $nLinks);
    }
}
