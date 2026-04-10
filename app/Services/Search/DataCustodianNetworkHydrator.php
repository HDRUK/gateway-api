<?php

namespace App\Services\Search;

use App\Models\Dataset;
use App\Models\DataProviderColl;
use Config;

class DataCustodianNetworkHydrator
{
    public function hydrate(array $hits): array
    {
        $matchedIds = array_map(fn ($h) => (int)$h['_id'], $hits);

        // Load networks with teams eager-loaded
        $models = DataProviderColl::whereIn('id', $matchedIds)
            ->with('teams')
            ->get()
            ->keyBy('id');

        // Batch-load all datasets for all teams across all networks in 1 query
        $allTeamIds = $models->flatMap(fn ($m) => $m->teams->pluck('id'))->unique()->all();

        $datasetsByTeam = !empty($allTeamIds)
            ? Dataset::where('status', 'ACTIVE')
                ->whereIn('team_id', $allTeamIds)
                ->with('latestMetadata')
                ->select(['id', 'team_id'])
                ->get()
                ->groupBy('team_id')
            : collect();

        foreach ($hits as $i => $hit) {
            $model = $models[(int)$hit['_id']] ?? null;
            if (!$model) {
                unset($hits[$i]);
                continue;
            }

            $hits[$i]['id'] = $model->id;
            $hits[$i]['_source']['updated_at'] = $model->updated_at;
            $hits[$i]['name'] = $model->name;
            $hits[$i]['img_url'] = $this->resolveImgUrl($model->img_url);
            $hits[$i]['datasetTitles'] = $this->buildDatasetTitles($model, $datasetsByTeam);
            $hits[$i]['geographicLocations'] = $this->buildLocations($model, $datasetsByTeam);
        }

        return array_values($hits);
    }

    private function buildDatasetTitles(DataProviderColl $provider, $datasetsByTeam): array
    {
        $titles = $provider->teams
            ->flatMap(fn ($team) => $datasetsByTeam->get($team->getKey(), collect()))
            ->map(fn ($dataset) => $dataset->latestMetadata?->short_title
                ?? $dataset->latestMetadata?->metadata['metadata']['summary']['shortTitle']
                ?? null)
            ->filter()
            ->sort()
            ->values()
            ->all();

        return $titles;
    }

    private function buildLocations(DataProviderColl $provider, $datasetsByTeam): array
    {
        return $provider->teams
            ->flatMap(fn ($team) => $datasetsByTeam->get($team->getKey(), collect()))
            ->flatMap(fn ($dataset) => $dataset->allSpatialCoverages)
            ->pluck('region')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveImgUrl(?string $imgUrl): ?string
    {
        if (is_null($imgUrl) || strlen(trim($imgUrl)) === 0) {
            return null;
        }
        if (preg_match('/^https?:\/\//', $imgUrl)) {
            return null; // V1 returns null for absolute URLs here
        }
        return Config::get('services.media.base_url') . $imgUrl;
    }
}
