<?php

namespace App\Services\Search;

use App\Models\Dataset;
use App\Models\DataAccessTemplate;
use App\Models\Team;
use Illuminate\Support\Arr;
use Config;

class DatasetHydrator
{
    public function hydrate(array $hits, string $viewType = 'full'): array
    {
        $matchedIds = array_map(fn($h) => (int)$h['_id'], $hits);

        // Single query for all datasets + team + latestMetadata (replaces N individual queries)
        $models = Dataset::with(['team', 'latestMetadata'])
            ->whereIn('id', $matchedIds)
            ->get()
            ->keyBy('id');

        // Batch-load DAR templates grouped by team_id (replaces 1 query per result)
        $teamIds = $models->pluck('team_id')->unique()->values()->all();

        $publishedDarByTeam = DataAccessTemplate::whereIn('team_id', $teamIds)
            ->where('published', 1)
            ->get()
            ->groupBy('team_id');

        // Batch-load DataProviderColl memberships (replaces 2 queries per result)
        $dataProviderCollsByTeam = DataProviderCollLoader::forTeamIds($teamIds);

        // Collect PIDs that need resolving (replaces 1 query per PID-format gatewayId)
        $pidsToResolve = [];
        foreach ($models as $model) {
            $latestVersion = $model->latestMetadata;
            if (!$latestVersion) {
                continue;
            }
            $gatewayId = Arr::get($latestVersion->metadata, 'metadata.summary.publisher.gatewayId');
            if ($gatewayId && str_contains((string)$gatewayId, '-')) {
                $pidsToResolve[] = $gatewayId;
            }
        }
        $teamsByPid = !empty($pidsToResolve)
            ? Team::whereIn('pid', array_unique($pidsToResolve))->get()->keyBy('pid')
            : collect();

        // Hydrate hits in O(1) per result using keyed collection
        foreach ($hits as $i => $hit) {
            $model = $models[(int)$hit['_id']] ?? null;
            if (!$model) {
                unset($hits[$i]);
                continue;
            }

            $latestVersion = $model->latestMetadata;
            if (!$latestVersion) {
                \Log::warning('No version found for dataset id=' . $model->id);
                unset($hits[$i]);
                continue;
            }

            $metadata = $latestVersion->metadata['metadata'] ?? null;
            if (!$metadata) {
                \Log::warning('Missing metadata structure for dataset version id=' . $latestVersion->id . ', dataset id=' . $model->id);
                unset($hits[$i]);
                continue;
            }

            // Resolve gatewayId (PID → integer ID)
            $gatewayId = $metadata['summary']['publisher']['gatewayId'] ?? null;
            if ($gatewayId && str_contains((string)$gatewayId, '-')) {
                $team = $teamsByPid->get($gatewayId);
                if ($team) {
                    $metadata['summary']['publisher']['gatewayId'] = $team->id;
                }
            } else {
                $metadata['summary']['publisher']['gatewayId'] = (int)$gatewayId;
            }

            $hits[$i]['_source']['created_at'] = $model->created_at;
            $hits[$i]['_source']['updated_at'] = $model->updated_at;

            $hits[$i]['metadata'] = strtolower($viewType) === 'mini'
                ? $this->trimPayload($metadata)
                : $metadata;

            $hits[$i]['isCohortDiscovery'] = $model->is_cohort_discovery;
            $hits[$i]['dataProviderColl'] = $dataProviderCollsByTeam->get($model->team_id, []);
            $hits[$i]['team'] = [
                'id'                        => $model->team->id,
                'is_question_bank'          => $model->team->is_question_bank,
                'has_published_dar_template'=> $publishedDarByTeam->has($model->team_id),
                'name'                      => $model->team->name,
                'member_of'                 => $model->team->member_of,
                'is_dar'                    => $model->team->is_dar,
                'dar_modal_header'          => $model->team->dar_modal_header,
                'dar_modal_content'         => $model->team->dar_modal_content,
                'dar_modal_footer'          => $model->team->dar_modal_footer,
            ];
        }

        return array_values($hits);
    }

    private function trimPayload(array $metadata): array
    {
        $materialTypes = $this->getMaterialTypes($metadata);
        $containsBioSamples = !empty($materialTypes);
        $hasTechnicalMetadata = (bool)count(Arr::get($metadata, 'structuralMetadata') ?? []);

        $accessServiceCategory = $metadata['accessibility']['access']['accessServiceCategory'] ?? null;

        $minimumKeys = ['summary', 'provenance', 'accessibility'];
        foreach (array_keys($metadata) as $key) {
            if (!in_array($key, $minimumKeys)) {
                unset($metadata[$key]);
            }
        }

        $metadata['additional']['containsBioSamples'] = $containsBioSamples;
        $metadata['accessibility']['access']['accessServiceCategory'] = $accessServiceCategory;
        $metadata['additional']['hasTechnicalMetadata'] = $hasTechnicalMetadata;

        return $metadata;
    }

    private function getMaterialTypes(array $metadata): ?array
    {
        if (version_compare(Config::get('metadata.GWDM.version'), '2.0', '<')) {
            return null;
        }

        $tissues = Arr::get($metadata, 'tissuesSampleCollection', null);
        if (is_null($tissues)) {
            return null;
        }

        $materialTypes = array_reduce($tissues, function ($carry, $item) {
            if (($item['materialType'] ?? '') !== 'None/not available') {
                $carry[] = $item['materialType'];
            }
            return $carry;
        }, []);

        return count($materialTypes) === 0 ? null : array_unique($materialTypes);
    }
}
