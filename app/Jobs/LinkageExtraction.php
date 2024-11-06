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
use App\Models\Dataset;

class LinkageExtraction implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected string $sourceDatasetId = '';
    protected string $sourceDatasetVersionId = '';
    protected array $linkages;
    protected string $description = '';

    /**
     * Create a new job instance.
     */
    public function __construct(string $datasetId, string $datasetVersionId, string $linkages)
    {
        $this->sourceDatasetId = $datasetId;
        $this->sourceDatasetVersionId = $datasetVersionId;
        $this->linkages = json_decode(gzdecode(gzuncompress(base64_decode($linkages))), true);
        $this->description = 'Extracted linkage from LinkageExtraction job';

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //delete any existing
        DatasetVersionHasDatasetVersion::where([
            'dataset_version_source_id' => $this->sourceDatasetId,
            'direct_linkage' => 1,
            'description' => $this->description
        ])->delete();

        foreach ($this->linkages as $key => $data) {
            if(!$data) {
                continue;
            }
            foreach ($data as $d) {
                $targetDatasetVersionId = $this->findTarget($d);
                if(!$targetDatasetVersionId) {
                    continue;
                }
                DatasetVersionHasDatasetVersion::updateOrCreate([
                    'dataset_version_source_id' => $this->sourceDatasetId,
                    'dataset_version_target_id' => $targetDatasetVersionId,
                    'linkage_type' => $key,
                    'direct_linkage' => 1,
                    'description' => $this->description
                ]);

            }
        }
    }

    public function findTarget(array $data): int|null
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

    public function tags(): array
    {
        return [
            'dataset:' . $this->sourceDatasetId,
            'version:' . $this->sourceDatasetVersionId,
        ];
    }
}
