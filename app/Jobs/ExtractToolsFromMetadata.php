<?php

namespace App\Jobs;

use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Dataset;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use App\Http\Traits\IndexElastic;
use App\Http\Traits\LoggingContext;
use App\Models\DatasetVersionHasTool;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExtractToolsFromMetadata implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use IndexElastic;
    use LoggingContext;

    private int $datasetVersionId = 0;
    private ?array $loggingContext = null;

    /**
     * Create a new job instance.
     */
    public function __construct(int $datasetVersionId)
    {
        $this->datasetVersionId = $datasetVersionId;
        $this->loggingContext = $this->getLoggingContext(\request());
        $this->loggingContext['method_name'] = class_basename($this);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->datasetVersionId) {
            return;
        }

        $this->tool($this->datasetVersionId);
    }

    public function tool(int $datasetVersionId)
    {
        $linkageToolsDataset = 'metadata.linkage.tools';
        $type = 'Used on';

        $metadata = \DB::table('dataset_versions')
            ->where('id', $datasetVersionId)
            ->select('id', 'dataset_id', 'metadata', \DB::raw('JSON_TYPE(metadata) as metadata_type'))
            ->first();

        if (is_null($metadata)) {
            \Log::info('ExtractToolsFromMetadata :: Metadata not found.', $this->loggingContext);
            return;
        }

        $dataset = Dataset::where('id', $metadata->dataset_id)->select(['id', 'user_id', 'team_id'])->first();
        if (is_null($dataset)) {
            \Log::info('ExtractToolsFromMetadata :: Dataset not found.', $this->loggingContext);
            return;
        }

        $user = User::where('id', $dataset->user_id)->first();
        if (is_null($user)) {
            \Log::info('ExtractToolsFromMetadata :: User not found.', $this->loggingContext);
            return;
        }

        $team = Team::where('id', $dataset->team_id)->first();
        if (is_null($team)) {
            \Log::info('ExtractToolsFromMetadata :: Team not found.', $this->loggingContext);
            return;
        }

        $datasetUserId = (int) $dataset->user_id;
        $datasetTeamId = (int) $dataset->team_id;

        $this->cleanToolDatasetVersion($datasetVersionId, $type, (int)$dataset->id);

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

        if (Arr::has($data, $linkageToolsDataset) && !is_null(data_get($data, $linkageToolsDataset))) {
            $this->toolDataset(data_get($data, $linkageToolsDataset, ''), $datasetVersionId, $datasetUserId, $datasetTeamId, $type);
        }
    }
    public function toolDataset(?string $tools, int $datasetVersionId, int $userId, int $teamId, string $type)
    {
        if (is_null($tools)) {
            return;
        }

        $arrTools = explode(';,;', $tools);

        foreach ($arrTools as $item) {
            \Log::info('ExtractToolsFromMetadata :: tool - ' . $item, $this->loggingContext);

            if (str_contains($item, env('GATEWAY_URL'))) {
                $exploded = explode('/', $item);
                $toolId = (int) end($exploded);
                $t = Tool::where('id', $toolId)->first();
                if (!is_null($t)) {
                    $this->createLinkToolDatasetVersion($toolId, $datasetVersionId, $type);
                    continue;
                }
            }
        }
    }

    public function cleanToolDatasetVersion(int $datasetVersionId, string $type, int $datasetId): void
    {
        $datasetVersionHasTools = DatasetVersionHasTool::where([
            'dataset_version_id' => $datasetVersionId,
            'link_type' => $type,
        ])->get();

        foreach ($datasetVersionHasTools as $datasetVersionHasTool) {
            $toolId = $datasetVersionHasTool->tool_id;

            DatasetVersionHasTool::where([
                'dataset_version_id' => $datasetVersionId,
                'link_type' => $type,
                'tool_id' => $toolId,
            ])
                ->delete();

            // note: not sure about this...
            // - shouldnt this be calling deleteFromElastic?
            // - you've just soft deleted it?
            $this->indexElasticTools((int) $toolId);
        }

        // why? the dataset index has nothing to do with tools
        // - the tool elastic index has something to do with datasets,
        //   not the other way around
        $this->reindexElastic((int) $datasetId);
    }

    public function createLinkToolDatasetVersion(int $toolId, int $datasetVersionId, string $type): ?DatasetVersionHasTool
    {
        $check = DatasetVersionHasTool::where([
            'tool_id' => $toolId,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => $type,
        ])
            ->first();

        if (is_null($check)) {
            // note: not sure about this..
            // - it could exist in the table but have been soft deleted?
            // - updateOrCreate instead to update the soft delete?
            DatasetVersionHasTool::create([
                'tool_id' => $toolId,
                'dataset_version_id' => $datasetVersionId,
                'link_type' => $type,
            ]);
            $this->indexElasticTools((int) $toolId);
            return null;
        }

        return null;
    }
}
