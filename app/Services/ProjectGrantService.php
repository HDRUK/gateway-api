<?php

namespace App\Services;

use App\Models\ProjectGrant;
use Illuminate\Pagination\LengthAwarePaginator;

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

        return ProjectGrant::query()
            ->when($pid, fn ($q) => $q->where('pid', '=', $pid))
            ->when($version, fn ($q) => $q->where('version', '=', $version))
            ->when($projectGrantName, fn ($q) => $q->where('projectGrantName', 'LIKE', '%' . $projectGrantName . '%'))
            ->when($userId, fn ($q) => $q->where('user_id', '=', $userId))
            ->when($teamId, fn ($q) => $q->where('team_id', '=', $teamId))
            ->when($withRelated, fn ($q) => $q->with(['datasetVersions', 'publications', 'tools']))
            ->when($sort, fn ($q) => $q->orderBy($sortField, $sortDirection))
            ->paginate($perPage, ['*'], 'page');
    }

    public function findById(int $projectGrantId, bool $withRelated): ?ProjectGrant
    {
        $query = ProjectGrant::withTrashed()->where(['id' => $projectGrantId]);
        if ($withRelated) {
            $query = $query->with(['datasetVersions', 'publications', 'tools']);
        }

        return $query->first();
    }
}

