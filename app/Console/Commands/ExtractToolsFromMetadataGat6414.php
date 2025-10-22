<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Dataset;
use Illuminate\Support\Arr;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasTool;
use Illuminate\Console\Command;

class ExtractToolsFromMetadataGat6414 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extract-tools-from-metadata-gat6414';

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
            $this->tools($datasetVersionId);
        }

        echo 'Completed ...' . PHP_EOL;
    }

    public function tools(int $datasetVersionId)
    {
        $linkageToolsDataset = 'metadata.linkage.tools';
        $type = 'Used on';

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
            $this->info($item);

            if (str_contains($item, config('gateway.gateway_url'))) {
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

    public function createLinkToolDatasetVersion(int $toolId, int $datasetVersionId, string $type)
    {
        return DatasetVersionHasTool::updateOrCreate([
            'tool_id' => $toolId,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => $type,
        ], [
            'tool_id' => $toolId,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => $type,
        ]);
    }
}
