<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\ProjectGrant;
use App\Models\ProjectGrantHasDatasetVersion;
use App\Models\ProjectGrantHasPublication;
use App\Models\ProjectGrantHasTool;
use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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
    public function handle(): void
    {
        if (!$this->datasetVersionId) {
            return;
        }

        $this->projectGrant($this->datasetVersionId);
    }

    /**
     * Extract project grant from dataset version metadata (aligned with ExtractPublicationsFromMetadata flow).
     */
    public function projectGrant(int $datasetVersionId): void
    {
        $metadata = \DB::table('dataset_versions')
            ->where('id', $datasetVersionId)
            ->select('id', 'dataset_id', 'version', 'metadata', DB::raw('JSON_TYPE(metadata) as metadata_type'))
            ->first();

        if (is_null($metadata)) {
            \Log::warning('ExtractProjectGrantsFromMetadata :: Metadata not found.', $this->loggingContext);
            return;
        }

        $dataset = Dataset::where('id', $metadata->dataset_id)->select(['id', 'pid', 'user_id', 'team_id'])->first();
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

        // Project is under `_extension.project` in the dataset metadata examples.
        $project = data_get($data, 'metadata._extension.project');
        if (empty($project)) {
            $project = data_get($data, '_extension.project');
        }
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

        $projectGrant = ProjectGrant::updateOrCreate(
            [
                'pid' => $dataset->pid,
                'version' => (int) $metadata->version,
                'projectGrantName' => $projectGrantName,
            ],
            [
                'user_id' => $datasetUserId,
                'team_id' => $datasetTeamId,
                'leadResearcher' => $project['leadResearcher'] ?? null,
                'leadResearchInstitute' => $project['leadResearchInstitute'] ?? null,
                'grantNumbers' => $grantNumbers,
                'projectGrantStartDate' => $project['projectStartDate'] ?? null,
                'projectGrantEndDate' => $project['projectEndDate'] ?? null,
                'projectGrantScope' => $project['projectScope'] ?? null,
            ]
        );

        // Rebuild links for this project grant (idempotent for metadata re-ingestion).
        ProjectGrantHasDatasetVersion::where('project_grant_id', $projectGrant->id)->delete();
        ProjectGrantHasPublication::where('project_grant_id', $projectGrant->id)->delete();
        ProjectGrantHasTool::where('project_grant_id', $projectGrant->id)->delete();

        ProjectGrantHasDatasetVersion::create([
            'project_grant_id' => $projectGrant->id,
            'dataset_version_id' => $datasetVersionId,
        ]);

        // Publications linked to this dataset version
        $publicationIds = DB::table('publication_has_dataset_version')
            ->join('publications', 'publications.id', '=', 'publication_has_dataset_version.publication_id')
            ->where('publication_has_dataset_version.dataset_version_id', $datasetVersionId)
            ->where('publications.status', 'ACTIVE')
            ->pluck('publication_has_dataset_version.publication_id')
            ->unique()
            ->values()
            ->all();

        foreach ($publicationIds as $publicationId) {
            ProjectGrantHasPublication::create([
                'project_grant_id' => $projectGrant->id,
                'publication_id' => (int) $publicationId,
            ]);
        }

        // Tools linked to this dataset version
        $toolIds = DB::table('dataset_version_has_tool')
            ->join('tools', 'tools.id', '=', 'dataset_version_has_tool.tool_id')
            ->where('dataset_version_has_tool.dataset_version_id', $datasetVersionId)
            ->where('tools.status', 'ACTIVE')
            ->pluck('dataset_version_has_tool.tool_id')
            ->unique()
            ->values()
            ->all();

        foreach ($toolIds as $toolId) {
            ProjectGrantHasTool::create([
                'project_grant_id' => $projectGrant->id,
                'tool_id' => (int) $toolId,
            ]);
        }
    }
}
