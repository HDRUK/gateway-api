<?php

namespace App\Services;

use App\Models\ProjectGrant;
use App\Models\ProjectGrantVersion;
use App\Models\ProjectGrantVersionHasDataset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProjectGrantService
{
    public function list(
        ?string $pid,
        ?int $version,
        ?string $projectGrantName,
        ?int $userId,
        ?int $teamId,
        bool $withRelated,
        int $perPage,
        string $sort
    ): LengthAwarePaginator {
        $tmp = explode(':', $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists(1, $tmp) ? $tmp[1] : 'asc';

        $query = ProjectGrant::query()
            ->when($pid, fn (Builder $q) => $q->where('pid', '=', $pid))
            ->when($userId, fn (Builder $q) => $q->where('user_id', '=', $userId))
            ->when($teamId, fn (Builder $q) => $q->where('team_id', '=', $teamId))
            ->when($version, function (Builder $q) use ($version) {
                $q->whereHas('versions', fn (Builder $v) => $v->where('version', '=', $version));
            })
            ->when($projectGrantName, function (Builder $q) use ($projectGrantName) {
                $q->whereHas('versions', fn (Builder $v) => $v->where('project_grant_name', 'LIKE', '%' . $projectGrantName . '%'));
            });

        if ($sortField === 'version') {
            $query->withMax('versions', 'version')
                ->orderBy('versions_max_version', $sortDirection);
        } else {
            $query->when($sort, fn (Builder $q) => $q->orderBy($sortField, $sortDirection));
        }

        if ($withRelated) {
            $query->with([
                'datasets',
                'versions' => function ($q) {
                    $q->orderByDesc('version');
                },
                'versions.publications',
                'versions.tools',
            ]);
        } else {
            $query->with('latestVersion');
        }

        return $query->paginate($perPage, ['*'], 'page');
    }

    public function findById(int $projectGrantId, bool $withRelated): ?ProjectGrant
    {
        $query = ProjectGrant::withTrashed()->where(['id' => $projectGrantId]);
        if ($withRelated) {
            $query = $query->with([
                'datasets',
                'versions' => function ($q) {
                    $q->orderByDesc('version');
                },
                'versions.publications',
                'versions.tools',
            ]);
        } else {
            $query = $query->with('latestVersion');
        }

        return $query->first();
    }

    public function create(array $data, int $userId, int $teamId): ProjectGrant
    {
        return DB::transaction(function () use ($data, $userId, $teamId) {
            $grant = ProjectGrant::create([
                'pid' => $data['pid'],
                'user_id' => $userId,
                'team_id' => $teamId,
            ]);

            $version = ProjectGrantVersion::create([
                'project_grant_id' => $grant->id,
                'version' => array_key_exists('version', $data) ? (int) $data['version'] : 1,
                'project_grant_name' => $data['projectGrantName'],
                'lead_researcher' => $data['leadResearcher'] ?? null,
                'lead_research_institute' => $data['leadResearchInstitute'] ?? null,
                'grant_numbers' => $data['grantNumbers'] ?? null,
                'project_grant_start_date' => $data['projectGrantStartDate'] ?? null,
                'project_grant_end_date' => $data['projectGrantEndDate'] ?? null,
                'project_grant_scope' => $data['projectGrantScope'] ?? null,
            ]);

            $this->syncDatasets($grant->id, $data['datasets'] ?? []);
            $this->syncVersionRelations($version, $data);

            return $grant;
        });
    }

    /**
     * Full replace: optionally update parent fields and always create a new version.
     */
    public function update(ProjectGrant $grant, array $data): ProjectGrant
    {
        return DB::transaction(function () use ($grant, $data) {
            $parentUpdates = [];
            if (array_key_exists('pid', $data)) {
                $parentUpdates['pid'] = $data['pid'];
            }
            if (!empty($parentUpdates)) {
                $grant->update($parentUpdates);
            }

            $nextVersion = array_key_exists('version', $data)
                ? (int) $data['version']
                : ((int) $grant->versions()->max('version')) + 1;

            $version = ProjectGrantVersion::create([
                'project_grant_id' => $grant->id,
                'version' => $nextVersion,
                'project_grant_name' => $data['projectGrantName'],
                'lead_researcher' => $data['leadResearcher'] ?? null,
                'lead_research_institute' => $data['leadResearchInstitute'] ?? null,
                'grant_numbers' => $data['grantNumbers'] ?? null,
                'project_grant_start_date' => $data['projectGrantStartDate'] ?? null,
                'project_grant_end_date' => $data['projectGrantEndDate'] ?? null,
                'project_grant_scope' => $data['projectGrantScope'] ?? null,
            ]);

            $this->syncDatasets($grant->id, $data['datasets'] ?? []);
            $this->syncVersionRelations($version, $data);

            return $grant->fresh();
        });
    }

    /**
     * Partial update of the parent and/or the latest version in place.
     */
    public function edit(ProjectGrant $grant, array $data): ProjectGrant
    {
        return DB::transaction(function () use ($grant, $data) {
            $parentUpdates = [];
            if (array_key_exists('pid', $data)) {
                $parentUpdates['pid'] = $data['pid'];
            }
            if (!empty($parentUpdates)) {
                $grant->update($parentUpdates);
            }

            $versionFieldMap = [
                'projectGrantName' => 'project_grant_name',
                'leadResearcher' => 'lead_researcher',
                'leadResearchInstitute' => 'lead_research_institute',
                'grantNumbers' => 'grant_numbers',
                'projectGrantStartDate' => 'project_grant_start_date',
                'projectGrantEndDate' => 'project_grant_end_date',
                'projectGrantScope' => 'project_grant_scope',
            ];

            $versionUpdates = [];
            foreach ($versionFieldMap as $inputKey => $column) {
                if (array_key_exists($inputKey, $data)) {
                    $versionUpdates[$column] = $data[$inputKey];
                }
            }

            $latestVersion = $grant->latestVersion;
            if (!empty($versionUpdates)) {
                if (!$latestVersion) {
                    $latestVersion = ProjectGrantVersion::create(array_merge([
                        'project_grant_id' => $grant->id,
                        'version' => 1,
                        'project_grant_name' => $data['projectGrantName'] ?? 'Untitled',
                    ], $versionUpdates));
                } else {
                    $latestVersion->update($versionUpdates);
                }
            }

            if (array_key_exists('datasets', $data)) {
                $this->syncDatasets($grant->id, $data['datasets'] ?? []);
            }

            if ($latestVersion) {
                $this->syncVersionRelations($latestVersion, $data, onlyProvided: true);
            }

            return $grant->fresh();
        });
    }

    public function delete(ProjectGrant $grant): bool
    {
        return DB::transaction(function () use ($grant) {
            ProjectGrantVersion::where('project_grant_id', $grant->id)->delete();
            $grant->delete();

            return true;
        });
    }

    private function syncDatasets(int $projectGrantId, array $datasetIds): void
    {
        $datasetIds = array_values(array_unique(array_map('intval', $datasetIds)));

        $query = ProjectGrantVersionHasDataset::where('project_grant_id', $projectGrantId);
        if (!empty($datasetIds)) {
            $query->whereNotIn('dataset_id', $datasetIds);
        }
        $query->delete();

        foreach ($datasetIds as $datasetId) {
            ProjectGrantVersionHasDataset::firstOrCreate([
                'project_grant_id' => $projectGrantId,
                'dataset_id' => $datasetId,
            ]);
        }
    }

    private function syncVersionRelations(ProjectGrantVersion $version, array $data, bool $onlyProvided = false): void
    {
        if (!$onlyProvided || array_key_exists('publications', $data)) {
            $version->publications()->sync($data['publications'] ?? []);
        }

        if (!$onlyProvided || array_key_exists('tools', $data)) {
            $version->tools()->sync($data['tools'] ?? []);
        }
    }
}
