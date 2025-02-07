<?php

namespace App\Jobs;

use Auditor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\PublicationHasDatasetVersion;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;

class LinkageExtraction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $sourceDatasetId = '';
    protected string $sourceDatasetVersionId = '';
    protected string $gwdmVersion = '';
    protected array|null $datasetLinkages;
    protected array|null $publicationAboutDatasetLinkages;
    protected array|null $publicationUsingDatasetLinkages;
    protected string $description = '';

    /**
     * Create a new job instance.
     */
    public function __construct(string $datasetId, string $datasetVersionId)
    {
        try {
            $this->sourceDatasetId = $datasetId;
            $this->sourceDatasetVersionId = $datasetVersionId;
            $version = DatasetVersion::findOrFail($datasetVersionId);

            $this->gwdmVersion = $version->metadata['gwdmVersion'];
            $this->datasetLinkages = $version->metadata['metadata']['linkage']['datasetLinkage'] ?? null;
            $this->publicationAboutDatasetLinkages = $version->metadata['metadata']['linkage']['publicationAboutDataset'] ?? null;
            $this->publicationUsingDatasetLinkages = $version->metadata['metadata']['linkage']['publicationUsingDataset'] ?? null;
            $this->description = 'Extracted from GWDM';

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => __METHOD__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Error initializing LinkageExtraction job: ' . $e->getMessage());
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (!version_compare($this->gwdmVersion, '2.0', '=')) {
                return; // Not the correct version for processing
            }

            // Process dataset and publication linkages
            $this->processDatasetLinkages();
            $this->processPublicationLinkages($this->publicationAboutDatasetLinkages, 'ABOUT');
            $this->processPublicationLinkages($this->publicationUsingDatasetLinkages, 'USING');

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => __METHOD__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Error handling LinkageExtraction job: ' . $e->getMessage());
        }
    }

    /**
     * Process dataset linkages.
     */
    protected function processDatasetLinkages(): void
    {
        try {
            DatasetVersionHasDatasetVersion::where([
                'dataset_version_source_id' => $this->sourceDatasetVersionId,
                'direct_linkage' => 1,
                'description' => $this->description
            ])->delete();

            if (is_null($this->datasetLinkages)) {
                return; // No datasets to process
            }

            foreach ($this->datasetLinkages as $key => $data) {
                if (!$data) {
                    continue;
                }
                foreach ($data as $d) {
                    $targetDatasetVersionId = $this->findTargetDataset($d);
                    if (!$targetDatasetVersionId) {
                        continue;
                    }
                    DatasetVersionHasDatasetVersion::updateOrCreate([
                        'dataset_version_source_id' => $this->sourceDatasetVersionId,
                        'dataset_version_target_id' => $targetDatasetVersionId,
                        'linkage_type' => $key,
                        'direct_linkage' => 1,
                        'description' => $this->description
                    ]);
                }
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => __METHOD__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Error processing dataset linkages: ' . $e->getMessage());
        }
    }

    /**
     * Generalized function to process publication linkages.
     */
    protected function processPublicationLinkages(?array $publicationLinkages, string $linkType): void
    {
        try {
            PublicationHasDatasetVersion::where([
                'dataset_version_id' => $this->sourceDatasetVersionId,
                'link_type' => $linkType,
                'description' => $this->description
            ])->delete();

            if (is_null($publicationLinkages)) {
                return; // No publications to process
            }

            foreach ($publicationLinkages as $data) {
                if (!$data) {
                    continue;
                }
                $publicationId = $this->findTargetPublication($data);
                if (!$publicationId) {

                    // THIS IS WHERE WE CAN SEARCH FOR NEW PUB AUTOMAGICALLY
                    continue;
                }

                $Search_array = [
                    'publication_id' => $publicationId,
                    'dataset_version_id' => $this->sourceDatasetVersionId,
                    'link_type' => $linkType,
                    'description' => $this->description,
                ];

                $arrCreate = [
                    'deleted_at' => null,
                ];

                PublicationHasDatasetVersion::withTrashed()->updateOrCreate($Search_array, $arrCreate);
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => __METHOD__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception("Error processing publication linkages ({$linkType}): " . $e->getMessage());
        }
    }

    /**
     * Find the target dataset version ID.
     */
    protected function findTargetDataset(array $data): ?int
    {
        $id = $data['url'] ?? null;
        $pid = $data['pid'] ?? null;
        $title = $data['title'] ?? null;

        if ($id) {
            $urlParts = explode('/', parse_url($id, PHP_URL_PATH));
            $id = end($urlParts);
            $dataset = Dataset::find($id);
            if ($dataset) {
                return $dataset->latestVersionID($dataset->id);
            }
        }

        if ($pid) {
            $dataset = Dataset::where('pid', $pid)->first();
            if ($dataset) {
                return $dataset->latestVersionID($dataset->id);
            }
        }

        if ($title) {
            $datasetVersion = DatasetVersion::filterTitle($title)->first();
            if ($datasetVersion) {
                return $datasetVersion->id;
            }
        }

        return null;
    }

    /**
     * Find the target publication ID.
     */
    protected function findTargetPublication(string $doi): ?int
    {
        // Search for publications with matching normalized DOI
        $publication = Publication::whereRaw(
            "REPLACE(REPLACE(paper_doi, 'https://doi.org/', ''), 'doi.org/', '') = ?",
            [$doi]
        )->first();

        return $publication?->id;
    }

    /**
     * Check if a query string is a DOI.
     */
    private function isDoi(string $query): bool
    {
        $pattern = '/10.\d{4,9}[-._;()\/:a-zA-Z0-9]+(?=[\s,\/]|$)/i';
        return (bool) preg_match($pattern, $query);
    }

    /**
     * Tags for the job.
     */
    public function tags(): array
    {
        return [
            'dataset:' . $this->sourceDatasetId,
            'version:' . $this->sourceDatasetVersionId,
        ];
    }
}
