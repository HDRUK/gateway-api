<?php

namespace App\Jobs;

use Auditor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
// use App\Http\Traits\LoggingContext;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\PublicationHasDatasetVersion;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;

class LinkageExtraction implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    // use LoggingContext;

    protected string $sourceDatasetId = '';
    protected string $sourceDatasetVersionId = '';
    protected string $gwdmVersion = '';
    protected array|null $datasetLinkages;
    protected array|null $publicationAboutDatasetLinkages;
    protected array|null $publicationUsingDatasetLinkages;
    protected string $description = '';

    // private ?array $loggingContext = null;

    /**
     * Create a new job instance.
     */
    public function __construct(string $datasetId, string $datasetVersionId)
    {
        try {

            $version = DatasetVersion::findOrFail($datasetVersionId);

            $this->sourceDatasetId = $datasetId;
            $this->sourceDatasetVersionId = $datasetVersionId;
            $this->gwdmVersion = $version->metadata['gwdmVersion'];

            $datasetLinkage = $version->metadata['metadata']['linkage']['datasetLinkage'] ?? null;
            $this->datasetLinkages = $datasetLinkage !== '' ? $datasetLinkage : null;

            $publicationAboutDatasetLinkages = $version->metadata['metadata']['linkage']['publicationAboutDataset'] ?? null;
            $this->publicationAboutDatasetLinkages = $publicationAboutDatasetLinkages !== '' ? $publicationAboutDatasetLinkages : null;

            $publicationUsingDatasetLinkages = $version->metadata['metadata']['linkage']['publicationUsingDataset'] ?? null;
            $this->publicationUsingDatasetLinkages = $publicationUsingDatasetLinkages !== '' ? $publicationUsingDatasetLinkages : null;

            $this->description = 'Extracted from GWDM';

            // $this->loggingContext = $this->getLoggingContext(\request());
            // $this->loggingContext['method_name'] = class_basename($this);

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => __METHOD__,
                'description' => $e->getMessage(),
            ]);

            // \Log::info('Error initializing LinkageExtraction job: ' . $e->getMessage(), $this->loggingContext);

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

            // \Log::info('Error handling LinkageExtraction job: ' . $e->getMessage(), $this->loggingContext);

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
                    DatasetVersionHasDatasetVersion::firstOrCreate([
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

            // \Log::info('Error processing dataset linkages: ' . $e->getMessage(), $this->loggingContext);

            throw new Exception('Error processing dataset linkages: ' . $e->getMessage());
        }
    }

    /**
     * Generalized function to process publication linkages.
     */
    protected function processPublicationLinkages(?array $publicationLinkages, string $linkType): void
    {
        try {
            // Clear any old linkages matching this description for the current dataset version
            PublicationHasDatasetVersion::where([
                'dataset_version_id' => $this->sourceDatasetVersionId,
                'description' => $this->description,
                'link_type' => $linkType,
            ])->delete();

            if (is_null($publicationLinkages)) {
                return; // No publications to process
            }

            foreach ($publicationLinkages as $doi) {
                if (!$doi) {
                    continue;
                }

                $publicationId = $this->findTargetPublication($doi);
                if (!$publicationId) {
                    // THIS IS WHERE WE CAN SEARCH FOR NEW PUB AUTOMAGICALLY
                    continue;
                }

                // Use firstOrCreate and restore if necessary
                $linkage = PublicationHasDatasetVersion::withTrashed()->firstOrCreate([
                    'publication_id' => $publicationId,
                    'dataset_version_id' => $this->sourceDatasetVersionId,
                    'link_type' => $linkType,
                    'description' => $this->description,
                ]);


                // Restore if it’s soft-deleted
                if ($linkage->trashed()) {
                    $linkage->restore();
                }
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => __METHOD__,
                'description' => $e->getMessage(),
            ]);

            // \Log::info("Error processing publication linkages ({$linkType}): " . $e->getMessage(), $this->loggingContext);

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
