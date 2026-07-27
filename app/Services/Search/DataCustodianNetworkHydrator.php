<?php

namespace App\Services\Search;

use App\Models\Dataset;
use App\Models\DataProviderColl;
use App\Models\DatasetVersion;
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

        // Batch-load all datasets for all teams across all networks in 1 query.
        // Only short_title is selected on latestMetadata here — NOT the full
        // GWDM metadata column, which runs ~120KB average and up to several MB
        // per row; loading it for every active dataset across a network's
        // member teams is what was causing OOM on networks with many datasets.
        $allTeamIds = $models->flatMap(fn ($m) => $m->teams->pluck('id'))->unique()->all();

        $datasetsByTeam = !empty($allTeamIds)
            ? Dataset::where('status', 'ACTIVE')
                ->whereIn('team_id', $allTeamIds)
                ->with(['latestMetadata' => fn ($q) => $q->select([
                    'dataset_versions.id',
                    'dataset_versions.dataset_id',
                    'dataset_versions.short_title',
                ])])
                ->select(['id', 'team_id'])
                ->get()
                ->groupBy('team_id')
            : collect();

        // Second pass: only for the minority of versions missing short_title
        // (a data-quality gap, not the common case) do we pay for the full
        // metadata column, and only for those specific rows.
        $versionIdsNeedingFallback = $datasetsByTeam->flatten()
            ->pluck('latestMetadata')
            ->filter(fn ($v) => $v !== null && empty($v->short_title))
            ->pluck('id');

        $fallbackMetadataByVersionId = $versionIdsNeedingFallback->isNotEmpty()
            ? DatasetVersion::whereIn('id', $versionIdsNeedingFallback)
                ->select(['id', 'metadata'])
                ->get()
                ->keyBy('id')
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
            $hits[$i]['datasetTitles'] = $this->buildDatasetTitles($model, $datasetsByTeam, $fallbackMetadataByVersionId);
            $hits[$i]['geographicLocations'] = $this->buildLocations($model, $datasetsByTeam);
        }

        return array_values($hits);
    }

    private function buildDatasetTitles(DataProviderColl $provider, $datasetsByTeam, $fallbackMetadataByVersionId): array
    {
        $titles = $provider->teams
            ->flatMap(fn ($team) => $datasetsByTeam->get($team->getKey(), collect()))
            ->map(function ($dataset) use ($fallbackMetadataByVersionId) {
                $version = $dataset->latestMetadata;
                if ($version === null) {
                    return null;
                }
                if (!empty($version->short_title)) {
                    return $version->short_title;
                }

                $fullVersion = $fallbackMetadataByVersionId->get($version->id);
                return $fullVersion?->metadata['metadata']['summary']['shortTitle'] ?? null;
            })
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
