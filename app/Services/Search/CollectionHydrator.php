<?php

namespace App\Services\Search;

use App\Models\Collection;
use Config;

class CollectionHydrator
{
    public function hydrate(array $hits): array
    {
        $matchedIds = array_map(fn($h) => (int)$h['_id'], $hits);

        $models = Collection::whereIn('id', $matchedIds)
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

            $hits[$i]['_source']['updated_at'] = $model->updated_at;
            $hits[$i]['name'] = $model->name;
            $hits[$i]['dataProviderColl'] = $dataProviderCollsByTeam->get($model->team_id, []);
            $hits[$i]['image_link'] = $this->resolveImageLink($model->image_link);
        }

        return array_values($hits);
    }

    private function resolveImageLink(?string $imageLink): ?string
    {
        if (is_null($imageLink) || strlen(trim($imageLink)) === 0) {
            return null;
        }
        if (preg_match('/^https?:\/\//', $imageLink)) {
            return $imageLink;
        }
        return Config::get('services.media.base_url') . $imageLink;
    }
}
