<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Console\Command;
use ElasticClientController as ECC;

class FindDuplicatePublicationsGat7698 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:find-duplicate-publications-gat7698 {datasetId?}';

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
        $datasetId = (int) $this->argument('datasetId') ?? $this->ask('Please enter the datasetId');
        $datasetVersion = DatasetVersion::where('dataset_id', $datasetId);
        $datasetVersionId = $datasetVersion->first()->id;

        dump('datasetId=' . $datasetId);
        dump('datasetVersionId=' . $datasetVersionId);

        $dois = json_decode(
            $datasetVersion
                ->selectRaw(
                    "JSON_EXTRACT(JSON_UNQUOTE(metadata),'$.metadata.linkage.publicationUsingDataset') as pubs"
                )
                ->first()->pubs,
            true
        );


        $publications = Publication::select(
            'publications.id',
            'publications.paper_doi',
            'publications.created_at',
            'publications.updated_at',
            'publications.deleted_at',
            'publications.owner_id',
            'publications.team_id'
        )
            ->join('publication_has_dataset_version', 'publications.id', '=', 'publication_has_dataset_version.publication_id')
            ->where('publication_has_dataset_version.dataset_version_id', $datasetVersionId)
            //->whereIn('publications.paper_doi', $dois)
            ->where(function ($query) use ($dois) {
                foreach ($dois as $doi) {
                    $query->orWhere('publications.paper_doi', 'like', "%{$doi}%");
                }
            })
            ->with('versions')
            ->orderBy('publications.updated_at')
            //->whereHas('team')
            ->get()
            ->map(function ($pub) use ($datasetVersionId) {
                $firstVersion = $pub->versions->where('id', $datasetVersionId)->first();

                return [
                    'id' => $pub->id,
                    'created_at' => $pub->created_at?->toISOString(),
                    'updated_at' => $pub->updated_at?->toISOString(),
                    'deleted_at' => $pub->deleted_at?->toISOString(),
                    'paper_doi' => $pub->paper_doi,
                    'owner' => $pub->owner->email,
                    'team' => $pub->team?->name,
                    'dataset_version_id' => $firstVersion?->id,
                    'dataset_id' => $firstVersion?->dataset_id,
                    'dataset_title' => $firstVersion?->short_title,
                ];
            });


        $publications = Publication::select(
            'publications.id',
            'publications.paper_doi',
            'publications.created_at',
            'publications.updated_at',
            'publications.deleted_at',
            'publications.owner_id',
            'publications.team_id'
        )
            ->join('publication_has_dataset_version', 'publications.id', '=', 'publication_has_dataset_version.publication_id')
            ->where('publication_has_dataset_version.dataset_version_id', $datasetVersionId)
            ->with('versions')
            ->orderBy('publications.updated_at')
            //->whereHas('team')
            ->get()
            ->map(function ($pub) use ($datasetVersionId) {
                $firstVersion = $pub->versions->where('id', $datasetVersionId)->first();

                return [
                    'id' => $pub->id,
                    'created_at' => $pub->created_at?->toISOString(),
                    'updated_at' => $pub->updated_at?->toISOString(),
                    'deleted_at' => $pub->deleted_at?->toISOString(),
                    'paper_doi' => $pub->paper_doi,
                    'owner' => $pub->owner->email,
                    'team' => $pub->team?->name,
                    'dataset_version_id' => $firstVersion?->id,
                    'dataset_id' => $firstVersion?->dataset_id,
                    'dataset_title' => $firstVersion?->short_title,
                ];
            });

        dump($publications);



        $csvFileName = storage_path('publications' . $datasetId . '.csv');
        $handle = fopen($csvFileName, 'w');

        // Write the header row
        if (!empty($publications)) {
            fputcsv($handle, array_keys($publications[0]));
        }

        // Write the data rows
        foreach ($publications as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        dump("CSV saved to: " . $csvFileName);

        dd(count($publications));









        $nLinks = PublicationHasDatasetVersion::where('dataset_version_id', $datasetVersionId)->count();

        dump('number of publications in metadata=' . count($dois));
        $publications = Publication::select('id', 'paper_doi', 'created_at', 'updated_at', 'deleted_at')
            ->where(function ($q) use ($dois) {
                //foreach ($dois as $doi) {
                //$q->orWhere('paper_doi', 'like', "%{$doi}%");
                //$q->orWhere('paper_doi', '=', $doi);
                //}
            })
            ->with('versions')
            ->orderBy('updated_at')
            ->get()
            ->map(function ($pub) {
                $firstVersion = $pub->versions->first();

                return [
                    'id' => $pub->id,
                    'created_at' => $pub->created_at?->toISOString(),
                    'updated_at' => $pub->updated_at?->toISOString(),
                    'deleted_at' => $pub->deleted_at?->toISOString(),
                    'paper_doi' => $pub->paper_doi,
                    'dataset_id' => $firstVersion?->dataset_id,
                    'dataset_title' => $firstVersion?->short_title,
                ];
            });

        dump('number of publications (from metadata) in publication table=' . count($publications));


        dump($publications->toArray());

        dd("------------");

        $duplicates = $publications
            ->groupBy('paper_doi')
            ->filter(function ($group) {
                return $group->count() > 1;
            });

        dump($duplicates->toArray());

        $nduplicates = $duplicates->count();

        dump('number of publication (from metadata) that have be duplicated=' . $nduplicates);

        $nunique = $publications->unique('paper_doi')->count();
        dump('number of publication (from metadata) that are unique=' . $nunique);


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

        $duplicates = $publications
            ->groupBy('clean_doi')
            ->filter(fn($group) => $group->count() > 1);

        $nduplicates = $duplicates->count();

        dump('number of publication (from metadata) that have be duplicated=' . $nduplicates);

        $nunique = $publications->unique('clean_doi')->count();
        dump('number of publication (from metadata) that are unique=' . $nunique);


        $nLinks = PublicationHasDatasetVersion::where('dataset_version_id', $datasetVersionId)->count();
        dump('number of existing publication links for this dataset=' . $nLinks);

        $nLinks = PublicationHasDatasetVersion::where('dataset_version_id', $datasetVersionId)
            ->whereNotIn('publication_id', $publications->pluck('id'))
            ->count();

        dump('number of existing publication links for this dataset, not in the metadata=' . $nLinks);

        $title = DatasetVersion::where('dataset_id', $datasetId)->first()->metadata['metadata']['summary']['shortTitle'];

        $n =  ECC::countDocuments(ECC::ELASTIC_NAME_PUBLICATION);
        dump('Total number of publications in elastic=' . $n);

        $n =  ECC::countDocuments(ECC::ELASTIC_NAME_PUBLICATION, 'datasetTitles', $title);
        dump('number of elastic matches to this title=' . $n);
    }
}
