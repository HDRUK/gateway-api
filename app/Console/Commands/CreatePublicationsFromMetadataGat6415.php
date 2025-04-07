<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\PublicationHasDatasetVersion;

class CreatePublicationsFromMetadataGat6415 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-publications-from-metadata-gat6415';

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
        $datasetVersions = DatasetVersion::select('id')->get();
        foreach ($datasetVersions as $datasetVersion) {
            $datasetVersionId = $datasetVersion->id;
            $this->info('DatasetVersion :: ' . $datasetVersionId);
            $this->publication($datasetVersionId);
        }

        echo 'Completed ...' . PHP_EOL;
    }

    public function publication($datasetVersionId)
    {
        $linkagePublicationAboutDataset = 'metadata.linkage.publicationAboutDataset';
        $linkagePublicationUsingDataset = 'metadata.linkage.publicationUsingDataset';

        $metadata = \DB::table('dataset_versions')
                ->where('id', $datasetVersionId)
                ->select('id', 'dataset_id', 'metadata', \DB::raw('JSON_TYPE(metadata) as metadata_type'))
                ->first();

        if (is_null($metadata)) {
            $this->warn('Metadata not found.');
            return;
        }

        $dataset = Dataset::where('id', $metadata->dataset_id)->select(['id', 'user_id', 'team_id'])->first();
        if (is_null($dataset)) {
            $this->warn('Dataset not found.');
            return;
        }

        $user = User::where('id', $dataset->user_id)->first();
        if (is_null($user)) {
            $this->warn('User not found.');
            return;
        }

        $team = Team::where('id', $dataset->team_id)->first();
        if (is_null($team)) {
            $this->warn('Team not found.');
            return;
        }

        $datasetUserId = (int) $dataset->user_id;
        $datasetTeamId = (int) $dataset->team_id;

        $data = null;
        if ($metadata->metadata_type === 'OBJECT') {
            $data = json_decode($metadata->metadata, true);
        }

        if ($metadata->metadata_type === 'STRING') {
            $data = json_decode(json_decode($metadata->metadata), true);
        }

        if (count($data ?: []) === 0) {
            return;
        }

        if (Arr::has($data, $linkagePublicationAboutDataset)) {
            $this->publicationDataset(is_array(data_get($data, $linkagePublicationAboutDataset, [])) ? data_get($data, $linkagePublicationAboutDataset, []) : [], 'ABOUT', $datasetVersionId, $datasetUserId, $datasetTeamId);
        }

        if (Arr::has($data, $linkagePublicationUsingDataset)) {
            $this->publicationDataset(is_array(data_get($data, $linkagePublicationUsingDataset, [])) ? data_get($data, $linkagePublicationUsingDataset, []) : [], 'USING', $datasetVersionId, $datasetUserId, $datasetTeamId);
        }
    }


    public function publicationDataset(array $publications, string $type, int $datasetVersionId, int $userId, int $teamId)
    {
        if (count($publications ?: []) === 0) {
            return;
        }

        foreach ($publications as $publication) {
            $this->info($publication);
            $checkPublication = Publication::where('paper_doi', 'like', '%' . $publication . '%')->first();

            if (!is_null($checkPublication)) {
                $this->info('Publication already exists.');
                $this->createLinkPublicationDatasetVersion($checkPublication->id, $datasetVersionId, $type);
                continue;
            }

            if (is_null($checkPublication)) {
                $searchDoi = $this->searchDoi($publication);
                if (is_null($searchDoi)) {
                    continue;
                }

                if ($searchDoi['data']['is_preprint']) {
                    $this->info('No publication - is_preprint is true');
                    continue;
                }

                $this->info(json_encode($searchDoi));
                $newPublication = new Publication();
                $newPublication->abstract = $searchDoi['data']['abstract'];
                $newPublication->authors = $searchDoi['data']['authors'];
                $newPublication->paper_title = $searchDoi['data']['title'];
                $newPublication->year_of_publication = $searchDoi['data']['publication_year'];
                $newPublication->publication_type = 'Research articles';
                $newPublication->paper_doi = $publication;
                $newPublication->journal_name = $searchDoi['data']['journal_name'];
                $newPublication->status = 'ACTIVE';
                $newPublication->owner_id = $userId;
                $newPublication->team_id = $teamId;

                if (!$searchDoi['data']['fullTextUrl'] || !is_array($searchDoi['data']['fullTextUrl'])) {
                    $newPublication->url = null;
                } else {
                    foreach ($searchDoi['data']['fullTextUrl'] as $fullTextUrl) {
                        if ($fullTextUrl['documentStyle'] === 'html') {
                            $newPublication->url = $fullTextUrl['url'];
                        }
                    }
                }

                $newPublication->save();
                $publicationId = $newPublication->id;
                $this->info($publicationId);

                $this->createLinkPublicationDatasetVersion($publicationId, $datasetVersionId, $type);
                continue;
            }

            $this->warn(json_encode($publication));
        }
    }

    public function searchDoi(string $doi)
    {
        $payload = [
            'query' => $doi,
        ];
        $url = env('APP_URL') . '/api/v1/search/doi';
        $response = Http::post($url, $payload);

        if ($response->successful()) {
            return $response->json();
        } else {
            $this->warn('Search Doi request failed');
            return null;
        }
    }

    public function createLinkPublicationDatasetVersion($publicationId, $datasetVersionId, $type)
    {
        return PublicationHasDatasetVersion::updateOrCreate([
            'publication_id' => $publicationId,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => $type,
        ], [
            'publication_id' => $publicationId,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => $type,
        ]);
    }
}
