<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use App\Models\ProjectGrant;
use App\Models\Tool;
use App\Services\DatasetService;
use App\Models\ProjectGrantVersionHasDataset;
use App\Models\ProjectGrantVersionHasPublication;
use App\Models\ProjectGrantVersionHasTool;
use App\Models\ProjectGrantVersion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Traits\LoggingContext;

class ExtractProjectGrantsFromMetadata implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
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
    public function handle(DatasetService $datasetService): void
    {
        if (!$this->datasetVersionId) {
            return;
        }

        $this->projectGrant($this->datasetVersionId, $datasetService);
    }

    /**
     * Extract project grant from dataset version metadata (aligned with ExtractPublicationsFromMetadata flow).
     */
    public function projectGrant(int $datasetVersionId, DatasetService $datasetService): void
    {
        $datasetVersion = DatasetVersion::where('id', $datasetVersionId)
            ->select(['id', 'dataset_id', 'version'])
            ->first();

        if (is_null($datasetVersion)) {
            \Log::warning('ExtractProjectGrantsFromMetadata :: Dataset version not found.', $this->loggingContext);
            return;
        }

        $dataset = Dataset::where('id', $datasetVersion->dataset_id)->select(['id', 'pid', 'user_id', 'team_id'])->first();
        if (is_null($dataset)) {
            \Log::warning('ExtractProjectGrantsFromMetadata :: Dataset not found.', $this->loggingContext);
            return;
        }

        $user = User::where('id', $dataset->user_id)->first();
        if (is_null($user)) {
            \Log::warning('ExtractProjectGrantsFromMetadata :: User not found.', $this->loggingContext);
            return;
        }

        $team = Team::where('id', $dataset->team_id)->first();
        if (is_null($team)) {
            \Log::warning('ExtractProjectGrantsFromMetadata :: Team not found.', $this->loggingContext);
            return;
        }

        $datasetUserId = (int) $dataset->user_id;
        $datasetTeamId = (int) $dataset->team_id;

        // Reconstruct full metadata for this version (delta patches are not self-contained).
        $data = $datasetService->getVersion($dataset, (int) $datasetVersion->version);
        if (empty($data)) {
            \Log::warning('ExtractProjectGrantsFromMetadata :: Metadata not found.', $this->loggingContext);
            return;
        }

        // Project is under `_extension.project` in the dataset metadata envelope.
        $project = data_get($data, 'metadata._extension.project');
        if (empty($project) || !is_array($project)) {
            return;
        }

        $projectGrantName = $project['projectName'] ?? null;
        if (empty($projectGrantName)) {
            return;
        }

        // Normalise grant numbers from metadata into an array for the JSON column.
        $grantNumbersRaw = $project['grantNumbers'] ?? null;
        $grantNumbers = [];
        if (is_string($grantNumbersRaw) && $grantNumbersRaw !== '') {
            // GWDM sometimes uses `;,;` as a joiner; also tolerate comma/semicolon.
            $parts = preg_split('/(;,;|;,|;|,)\s*/', $grantNumbersRaw);
            $grantNumbers = array_values(array_filter(array_map('trim', $parts)));
        } elseif (is_array($grantNumbersRaw)) {
            $grantNumbers = array_values(array_filter($grantNumbersRaw));
        }

        // Stable identity: one ProjectGrant per dataset PID; versioned fields live on ProjectGrantVersion.
        $projectGrant = ProjectGrant::firstOrCreate(
            ['pid' => $dataset->pid],
            [
                'user_id' => $datasetUserId,
                'team_id' => $datasetTeamId,
            ]
        );

        $projectGrant->update([
            'user_id' => $datasetUserId,
            'team_id' => $datasetTeamId,
        ]);

        $projectGrantVersion = ProjectGrantVersion::updateOrCreate(
            [
                'project_grant_id' => $projectGrant->id,
                'version' => (int) $datasetVersion->version,
            ],
            [
                'project_grant_name' => $projectGrantName,
                'lead_researcher' => $project['leadResearcher'] ?? null,
                'lead_research_institute' => $project['leadResearchInstitute'] ?? null,
                'grant_numbers' => $grantNumbers,
                'project_grant_start_date' => $project['projectStartDate'] ?? null,
                'project_grant_end_date' => $project['projectEndDate'] ?? null,
                'project_grant_scope' => $project['projectScope'] ?? null,
            ]
        );

        // Ensure stable dataset-level link exists for this grant.
        ProjectGrantVersionHasDataset::firstOrCreate([
            'project_grant_id' => $projectGrant->id,
            'dataset_id' => (int) $dataset->id,
        ]);

        // Rebuild version-scoped links for this version row (idempotent for metadata re-ingestion).
        ProjectGrantVersionHasPublication::where('project_grant_version_id', $projectGrantVersion->id)->delete();
        ProjectGrantVersionHasTool::where('project_grant_version_id', $projectGrantVersion->id)->delete();

        // Publications linked to this dataset version
        $publicationIds = Publication::query()
            ->where('status', Publication::STATUS_ACTIVE)
            ->whereIn(
                'id',
                PublicationHasDatasetVersion::query()
                    ->where('dataset_version_id', $datasetVersionId)
                    ->select('publication_id')
            )
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        foreach ($publicationIds as $publicationId) {
            ProjectGrantVersionHasPublication::create([
                'project_grant_version_id' => $projectGrantVersion->id,
                'publication_id' => (int) $publicationId,
            ]);
        }

        // Tools linked to this dataset version
        $toolIds = $datasetVersion->tools()
            ->where('status', Tool::STATUS_ACTIVE)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        foreach ($toolIds as $toolId) {
            ProjectGrantVersionHasTool::create([
                'project_grant_version_id' => $projectGrantVersion->id,
                'tool_id' => (int) $toolId,
            ]);
        }
    }
}
