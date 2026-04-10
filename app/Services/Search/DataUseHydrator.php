<?php

namespace App\Services\Search;

use App\Models\Dur;

class DataUseHydrator
{
    public function hydrate(array $hits): array
    {
        $matchedIds = array_map(fn ($h) => (int)$h['_id'], $hits);

        // Single query with all relationships (replaces per-DUR queries for tools, collections, versions)
        $models = Dur::with([
            'versions',      // DatasetVersions, for dataset titles
            'tools',         // already filters status=ACTIVE in relationship
            'collections',   // already filters status=ACTIVE in relationship
            'team',
        ])
            ->whereIn('id', $matchedIds)
            ->where('status', 'ACTIVE')
            ->get()
            ->keyBy('id');

        $teamIds = $models->pluck('team_id')->unique()->filter()->values()->all();
        $dataProviderCollsByTeam = DataProviderCollLoader::forTeamIds($teamIds);

        foreach ($hits as $i => $hit) {
            $model = $models[(int)$hit['_id']] ?? null;
            if (!$model) {
                unset($hits[$i]);
                continue;
            }

            $datasetTitles = $this->buildDatasetTitles($model);

            $hits[$i]['_source']['created_at'] = $model->created_at;
            $hits[$i]['_source']['updated_at'] = $model->updated_at;
            $hits[$i]['projectTitle'] = $model->project_title;
            $hits[$i]['organisationName'] = $model->organisation_name;
            $hits[$i]['team'] = $model->team;
            $hits[$i]['mongoObjectId'] = $model->mongo_object_id;
            $hits[$i]['datasetTitles'] = array_column($datasetTitles, 'title');
            $hits[$i]['datasetIds'] = array_column($datasetTitles, 'id');
            $hits[$i]['dataProviderColl'] = $dataProviderCollsByTeam->get($model->team_id, []);
            $hits[$i]['toolNames'] = $model->tools->pluck('name')->all();
            $hits[$i]['non_gateway_datasets'] = $model->non_gateway_datasets;
            $hits[$i]['collectionNames'] = $model->collections->pluck('name')->all();
        }

        return array_values($hits);
    }

    private function buildDatasetTitles(Dur $dur): array
    {
        $titles = $dur->versions
            ->map(fn ($version) => [
                'title' => $version->short_title
                    ?? $version->metadata['metadata']['summary']['shortTitle']
                    ?? '',
                'id'    => $version->dataset_id,
            ])
            ->filter(fn ($t) => !empty($t['title']))
            ->all();

        usort($titles, fn ($a, $b) => strcasecmp($a['title'], $b['title']));

        return $titles;
    }
}
