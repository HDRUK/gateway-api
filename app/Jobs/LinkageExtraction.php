<?php

namespace App\Jobs;

use Auditor;
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
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        $this->sourceDatasetId = $datasetId;
        $this->sourceDatasetVersionId = $datasetVersionId;
        $version = DatasetVersion::findOrFail($datasetVersionId);

        $this->gwdmVersion = $version->metadata['gwdmVersion'];
        $this->datasetLinkages = $version->metadata['metadata']['linkage']['datasetLinkage'] ?? null;
        $this->publicationAboutDatasetLinkages = $version->metadata['metadata']['linkage']['publicationAboutDataset'] ?? null;
        $this->publicationUsingDatasetLinkages = $version->metadata['metadata']['linkage']['publicationUsingDataset'] ?? null;
        $this->description = 'Extracted from GWDM';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if(!version_compare($this->gwdmVersion, '2.0', '=')) {
            return; // Not the correct version for processing
        }

        // Process dataset and publication linkages
        $this->processDatasetLinkages();
        $this->processPublicationAboutLinkages();
        $this->processPublicationUsingLinkages();
    }

    /**
     * Process dataset linkages.
     */
    protected function processDatasetLinkages(): void
    {
        DatasetVersionHasDatasetVersion::where([
            'dataset_version_source_id' => $this->sourceDatasetVersionId,
            'direct_linkage' => 1,
            'description' => $this->description
        ])->delete();

        if(is_null($this->datasetLinkages)) {
            return; // No datasets to process
        }

        foreach($this->datasetLinkages as $key => $data) {
            if(!$data) {
                continue;
            }
            foreach($data as $d) {
                $targetDatasetVersionId = $this->findTargetDataset($d);
                if(!$targetDatasetVersionId) {
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
    }

    /**
     * Process publication linkages for "About".
     */
    protected function processPublicationAboutLinkages(): void
    {
        PublicationHasDatasetVersion::where([
            'dataset_version_id' => $this->sourceDatasetVersionId,
            'link_type' => 'ABOUT',
            'description' => $this->description
            
        ])->delete();

        if(is_null($this->publicationAboutDatasetLinkages)) {
            return; // No publications to process
        }

        foreach($this->publicationAboutDatasetLinkages as $data) {
            if(!$data) {
                continue;
            }
            $publicationId = $this->findTargetPublication($data);
            if(!$publicationId) {
                continue;
            }
            PublicationHasDatasetVersion::updateOrCreate([
                'publication_id' => $publicationId,
                'dataset_version_id' => $this->sourceDatasetVersionId,
                'link_type' => 'ABOUT',
                'description' => $this->description
            ]);
        }
    }

    /**
     * Process publication linkages for "Using".
     */
    protected function processPublicationUsingLinkages(): void
    {
        PublicationHasDatasetVersion::where([
            'dataset_version_id' => $this->sourceDatasetVersionId,
            'link_type' => 'USING',
            'description' => $this->description
        ])->delete();

        if(is_null($this->publicationUsingDatasetLinkages)) {
            return; // No publications to process
        }

        foreach($this->publicationUsingDatasetLinkages as $data) {
            if(!$data) {
                continue;
            }
            $publicationId = $this->findTargetPublication($data);
            if(!$publicationId) {
                continue;
            }
            PublicationHasDatasetVersion::updateOrCreate([
                'publication_id' => $publicationId,
                'dataset_version_id' => $this->sourceDatasetVersionId,
                'link_type' => 'USING',
                'description' => $this->description
            ]);
        }
    }

    /**
     * Find the target dataset version ID.
     */
    protected function findTargetDataset(array $data): int|null
    {
        $id = $data['url'] ?? null;
        $pid = $data['pid'] ?? null;
        $title = $data['title'] ?? null;

        if($id) {
            $urlParts = explode('/', parse_url($id, PHP_URL_PATH));
            $id = end($urlParts);
            $dataset = Dataset::find($id);
            if($dataset) {
                return $dataset->latestVersionID($dataset->id);
            }
        }

        if($pid) {
            $dataset = Dataset::where('pid', $pid)->first();
            if($dataset) {
                return $dataset->latestVersionID($dataset->id);
            }
        }

        if($title) {
            $datasetVersion = DatasetVersion::filterTitle($title)->first();
            if($datasetVersion) {
                return $datasetVersion->id;
            }
        }

        // Return null if no match is found(no exception needed)
        return null;
    }

    /**
     * Find the target publication ID.
     */
    protected function findTargetPublication(array $data): int|null
    {
        $doi = $data['paper_doi'] ?? null;

        if ($doi) {
            // Normalize the input DOI from metadata (already in the 10.xxxx/xxxx format)
            $normalizedDoi = $doi;

            // Attempt to match against possible DOI formats in the publication table
            $publication = Publication::where(function($query) use ($normalizedDoi) {
                $query->where('paper_doi', $normalizedDoi)
                    ->orWhere('paper_doi', 'like', "%doi.org/%" . $normalizedDoi)
                    ->orWhere('paper_doi', 'like', "%doi/%" . $normalizedDoi)
                    ->orWhere('paper_doi', 'like', "%https://doi.org/%" . $normalizedDoi);
            })->first();

            if ($publication) {
                return $publication->id;
            }
        }

        // Return null if no publication match is found
        return null;
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
