<?php

namespace App\Services;

use App\Models\ProjectGrant;
use Illuminate\Database\Eloquent\Builder;
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
}
