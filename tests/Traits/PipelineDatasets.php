<?php

namespace Tests\Traits;

use App\Enums\DatasetOrigin;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use App\Services\Gwdm\SavedBy;
use Config;

trait PipelineDatasets
{
    protected function datasetEditedThroughThePipeline(int $teamId, string $title, string $shortTitle): Dataset
    {
        return $this->renameThroughThePipeline($this->datasetSavedThroughThePipeline($teamId), $title, $shortTitle);
    }

    protected function datasetSavedThroughThePipeline(int $teamId): Dataset
    {
        $team = Team::findOrFail($teamId);
        $editor = User::factory()->create();

        $created = app(DatasetService::class)->create(
            [
                'metadata' => $this->getMetadata(),
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $editor->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            SavedBy::person($editor->id, DatasetOrigin::MANUAL),
            null,
            null,
            targetGwdmVersion: Config::get('metadata.GWDM.version'),
        );

        return Dataset::findOrFail($created['dataset_id']);
    }

    protected function renameThroughThePipeline(Dataset $dataset, string $title, string $shortTitle): Dataset
    {
        $metadata = $this->getMetadata();
        $metadata['metadata']['summary']['title'] = $title;
        $metadata['metadata']['summary']['shortTitle'] = $shortTitle;

        app(DatasetService::class)->update(
            dataset: $dataset,
            input: ['metadata' => $metadata, 'status' => Dataset::STATUS_ACTIVE],
            userId: User::factory()->create()->id,
            origin: DatasetOrigin::MANUAL,
            partnerContext: null,
            targetGwdmVersion: Config::get('metadata.GWDM.version'),
        );

        $latest = DatasetVersion::where('dataset_id', $dataset->id)->orderByDesc('version')->firstOrFail();
        $this->assertNotNull($latest->patch, 'The latest version should be stored as changes from the one before');

        return $dataset->refresh();
    }
}
