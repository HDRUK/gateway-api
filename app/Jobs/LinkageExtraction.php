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
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected string $sourceDatasetId = '';
    protected string $sourceDatasetVersionId = '';
    protected string $gwdmVersion = '';
    protected array|null $datasetLinkages;
    protected array|null $publicationLinkages;
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
        $this->publicationLinkages = $version->metadata['metadata']['linkage']['publications'] ?? null;
        $this->description = 'Extracted from GWDM';
    
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if(!version_compare($this->gwdmVersion, '2.0', '=')) {
            throw new Exception("LinkageExtraction only supported for GWDM v2.0");
        }

        // Process dataset linkages
        $this->processDatasetLinkages();

        // Process publication linkages
        $this->processPublicationLinkages();
    }

    /**
     * Process dataset linkages.
     */
    protected function processDatasetLinkages(): void
    {
        if (is_null($this->datasetLinkages)) {
            throw new Exception("LinkageExtraction cannot find dataset linkages in the data");
        }

        DatasetVersionHasDatasetVersion::where([
            'dataset_version_source_id' => $this->sourceDatasetVersionId,
            'direct_linkage' => 1,
            'description' => $this->description
        ])->delete();

        foreach ($this->datasetLinkages as $key => $data) {
            if(!$data) {
                continue;
            }
            foreach ($data as $d) {
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
     * Process publication linkages.
     */
    protected function processPublicationLinkages(): void
    {
        if(is_null($this->publicationLinkages)) {
            return; // No publications to process
        }

        PublicationHasDatasetVersion::where([
            'dataset_version_id' => $this->sourceDatasetVersionId,
        ])->delete();

        foreach($this->publicationLinkages as $data) {
            if (!$data) {
                continue;
            }
            $publicationId = $this->findTargetPublication($data);
            if (!$publicationId) {
                continue;
            }
            PublicationHasDatasetVersion::updateOrCreate([
                'publication_id' => $publicationId,
                'dataset_version_id' => $this->sourceDatasetVersionId,
                'link_type' => $data['link_type'] ?? 'USING',
                'deleted_at' => null
            ]);
        }
    }

    /**
     * Find the target dataset version ID.
     */
    protected function findTargetDataset(array $data): int|null
    {
        try {
            $id = null;
            if (isset($data['url'])) {
                $urlParts = explode('/', parse_url($data['url'], PHP_URL_PATH));
                $id = end($urlParts);
            }
            $pid = $data['pid'] ?? null;
            $title = $data['title'] ?? null;

            if ($id) {
                // Find by id if pid is null and id is available
                $dataset = Dataset::find($id);
                if($dataset) {
                    return $dataset->latestVersionID($dataset->id);
                }
            }

            if ($pid) {
                // Find by pid if it exists
                $dataset = Dataset::where('pid', $pid)->first();
                if($dataset) {
                    return $dataset->latestVersionID($dataset->id);
                }
            }

            if ($title) {
                $datasetVersion = DatasetVersion::filterTitle($title)->first();
                if($datasetVersion) {
                    return $datasetVersion->id;
                }
            }
            return null;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Find the target publication ID.
     */
    protected function findTargetPublication(array $data): int|null
    {
        try {
            $doi = $data['doi'] ?? null;
            $title = $data['title'] ?? null;

            if ($doi) {
                $publication = Publication::where('doi', $doi)->first();
                if ($publication) {
                    return $publication->id;
                }
            }

            if ($title) {
                $publication = Publication::filterTitle($title)->first();
                if ($publication) {
                    return $publication->id;
                }
            }

            return null;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
        
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
