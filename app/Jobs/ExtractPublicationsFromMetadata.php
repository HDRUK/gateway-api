<?php

namespace App\Jobs;

use CloudLogger;
use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Models\Publication;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Support\Facades\Log;

class ExtractPublicationsFromMetadata implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $datasetVersionId = 0;

    /**
     * Create a new job instance.
     */
    public function __construct(int $datasetVersionId)
    {
        $this->datasetVersionId = $datasetVersionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->datasetVersionId) {
            return;
        }

        $this->publication($this->datasetVersionId);
    }

    public function publication($datasetVersionId)
    {
        Log::info("publication Start Memory usage: " . number_format(memory_get_usage(true) / 1024 / 1024, 2) . " MB");
        Log::info("publication Start Peak memory usage: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB");

        $linkagePublicationAboutDataset = 'metadata.linkage.publicationAboutDataset';
        $linkagePublicationUsingDataset = 'metadata.linkage.publicationUsingDataset';

        $metadata = \DB::table('dataset_versions')
                ->where('id', $datasetVersionId)
                ->select('id', 'dataset_id', 'metadata', \DB::raw('JSON_TYPE(metadata) as metadata_type'))
                ->first();

        if (is_null($metadata)) {
            CloudLogger::write('ExtractPublicationsFromMetadata :: Metadata not found.', 'WARNING');
            return;
        }

        $dataset = Dataset::where('id', $metadata->dataset_id)->select(['id', 'user_id', 'team_id'])->first();
        if (is_null($dataset)) {
            CloudLogger::write('ExtractPublicationsFromMetadata :: Dataset not found.', 'WARNING');
            return;
        }

        $user = User::where('id', $dataset->user_id)->first();
        if (is_null($user)) {
            CloudLogger::write('ExtractPublicationsFromMetadata :: User not found.', 'WARNING');
            return;
        }

        $team = Team::where('id', $dataset->team_id)->first();
        if (is_null($team)) {
            CloudLogger::write('ExtractPublicationsFromMetadata :: Team not found.', 'WARNING');
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
        Log::info("Start Memory usage: " . number_format(memory_get_usage(true) / 1024 / 1024, 2) . " MB");
        Log::info("Start Peak memory usage: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB");
        if (count($publications ?: []) === 0) {
            return;
        }

        foreach ($publications as $publication) {
            CloudLogger::write('ExtractPublicationsFromMetadata :: ' . $publication);

            // check if gateway url
            if (str_contains($publication, env('GATEWAY_URL'))) {
                $exploded = explode('/', $publication);
                $publicationId = (int) end($exploded);
                $pub = Publication::where('id', $publicationId)->first();
                if (!is_null($pub)) {
                    $this->createLinkPublicationDatasetVersion($publicationId, $datasetVersionId, $type);
                    continue;
                }
            }

            $checkPublication = Publication::where('paper_doi', 'like', '%' . $publication . '%')->first();

            if (!is_null($checkPublication)) {
                CloudLogger::write('ExtractPublicationsFromMetadata :: Publication already exists.', 'WARNING');
                $this->createLinkPublicationDatasetVersion($checkPublication->id, $datasetVersionId, $type);
                continue;
            }

            if (is_null($checkPublication)) {
                $searchDoi = $this->searchDoi($publication);
                if (is_null($searchDoi)) {
                    continue;
                }

                if ($searchDoi['data']['is_preprint']) {
                    CloudLogger::write('ExtractPublicationsFromMetadata :: No publication - is_preprint is true', 'WARNING');
                    continue;
                }

                CloudLogger::write('ExtractPublicationsFromMetadata :: search doi ' . json_encode($searchDoi));

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
                CloudLogger::write('ExtractPublicationsFromMetadata :: a new publication has been created with id = ' . $publicationId);

                $this->createLinkPublicationDatasetVersion($publicationId, $datasetVersionId, $type);

                continue;
            }
            unset($newPublication);
            unset($searchDoi);
            //gc_collect_cycles();
        }
        Log::info("End Memory usage: " . number_format(memory_get_usage(true) / 1024 / 1024, 2) . " MB");
        Log::info("End Peak memory usage: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB");
    }

    public function searchDoi(string $doi)
    {
        if (strlen($doi) === 0 || !$doi) {
            CloudLogger::write('ExtractPublicationsFromMetadata :: doi string invalid.', 'WARNING');
            return null;
        }

        $payload = [
            'query' => $doi,
        ];
        $url = env('APP_URL') . '/api/v1/search/doi';
        $response = Http::post($url, $payload);

        if ($response->successful()) {
            return $response->json();
        } else {
            CloudLogger::write('ExtractPublicationsFromMetadata :: Search Doi request failed', 'WARNING');
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
