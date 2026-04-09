<?php

namespace App\Services\Search;

use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use Illuminate\Support\Collection;

class DataProviderCollLoader
{
    /**
     * Batch-load DataProviderColl memberships for a set of team IDs.
     * Returns a Collection keyed by team_id, each value being an array
     * of ['id' => ..., 'name' => ...] entries.
     */
    public static function forTeamIds(array $teamIds): Collection
    {
        if (empty($teamIds)) {
            return collect();
        }

        $pivots = DataProviderCollHasTeam::whereIn('team_id', $teamIds)
            ->get()
            ->groupBy('team_id');

        $allCollIds = $pivots->flatten()->pluck('data_provider_coll_id')->unique()->all();

        if (empty($allCollIds)) {
            return collect();
        }

        $colls = DataProviderColl::whereIn('id', $allCollIds)
            ->select(['id', 'name'])
            ->get()
            ->keyBy('id');

        return $pivots->map(fn($rows) =>
            $rows->pluck('data_provider_coll_id')
                ->map(fn($id) => $colls->get($id))
                ->filter()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
                ->values()
                ->all()
        );
    }
}
