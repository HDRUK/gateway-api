<?php

namespace App\Services\Search;

use App\Models\Dataset;
use App\Models\Tool;

class ToolHydrator
{
    public function hydrate(array $hits): array
    {
        $matchedIds = array_map(fn ($h) => (int)$h['_id'], $hits);

        // Single query with all relationships eager-loaded (replaces ~15 queries per result)
        $models = Tool::whereIn('id', $matchedIds)
            ->with([
                'tag',
                'user',
                'team',
                'license',
                'programmingLanguages',
                'programmingPackages',
                'typeCategory',
                'versions',  // DatasetVersions linked to the tool
                'durs',      // already filters ACTIVE in relationship
            ])
            ->get()
            ->keyBy('id');

        // Batch-load dataset short titles for all tool-linked dataset versions
        $allDatasetIds = $models
            ->flatMap(fn ($t) => $t->versions->pluck('dataset_id'))
            ->unique()
            ->all();

        $datasetTitleMap = !empty($allDatasetIds)
            ? Dataset::with('latestMetadata')
                ->whereIn('id', $allDatasetIds)
                ->get()
                ->keyBy('id')
                ->map(fn ($d) => $d->latestMetadata?->metadata['metadata']['summary']['shortTitle'] ?? '')
                ->filter()
            : collect();

        // Batch-load DataProviderColl memberships
        $teamIds = $models->pluck('team_id')->unique()->filter()->values()->all();
        $dataProviderCollsByTeam = DataProviderCollLoader::forTeamIds($teamIds);

        foreach ($hits as $i => $hit) {
            $model = $models[(int)$hit['_id']] ?? null;
            if (!$model) {
                unset($hits[$i]);
                continue;
            }

            $hits[$i]['name'] = $model->name;
            $hits[$i]['description'] = $model->description;
            $hits[$i]['associatedAuthors'] = $model->associated_authors;
            $hits[$i]['tag'] = $model->tag;

            /** @phpstan-ignore-next-line */
            $hits[$i]['uploader'] = $model->user?->name ?? '';
            /** @phpstan-ignore-next-line */
            $hits[$i]['team_name'] = $model->team?->name ?? '';

            $hits[$i]['type_category'] = $model->typeCategory->pluck('name')->all();
            /** @phpstan-ignore-next-line */
            $hits[$i]['license'] = $model->license?->label ?? '';
            $hits[$i]['programming_language'] = $model->programmingLanguages->pluck('name')->all();
            $hits[$i]['programming_package'] = $model->programmingPackages->pluck('name')->all();

            $datasetTitles = $model->versions
                ->pluck('dataset_id')
                ->unique()
                ->map(fn ($id) => $datasetTitleMap->get($id))
                ->filter()
                ->sort()
                ->values()
                ->all();
            $hits[$i]['datasets'] = $datasetTitles;

            $hits[$i]['dataProviderColl'] = $dataProviderCollsByTeam->get($model->team_id, []);
            $hits[$i]['durTitles'] = $model->durs->pluck('project_title')->all();

            $hits[$i]['_source']['programmingLanguage'] = $model->tech_stack;
            /** @phpstan-ignore-next-line */
            $hits[$i]['_source']['category'] = $model->category?->name;
            $hits[$i]['_source']['created_at'] = $model->created_at;
            $hits[$i]['_source']['updated_at'] = $model->updated_at;
        }

        return array_values($hits);
    }
}
