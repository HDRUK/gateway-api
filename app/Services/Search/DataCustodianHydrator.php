<?php

namespace App\Services\Search;

use App\Models\Team;
use Config;

class DataCustodianHydrator
{
    public function hydrate(array $hits): array
    {
        $matchedIds = array_map(fn ($h) => (int)$h['_id'], $hits);

        $models = Team::whereIn('id', $matchedIds)
            ->get()
            ->keyBy('id');

        foreach ($hits as $i => $hit) {
            $model = $models[(int)$hit['_id']] ?? null;
            if (!$model) {
                unset($hits[$i]);
                continue;
            }

            $hits[$i]['_source']['updated_at'] = $model->updated_at;
            $hits[$i]['name'] = $model->name;
            $hits[$i]['team_logo'] = $this->resolveTeamLogo($model->team_logo);
        }

        return array_values($hits);
    }

    private function resolveTeamLogo(?string $teamLogo): string
    {
        if (is_null($teamLogo) || strlen(trim($teamLogo)) === 0) {
            return '';
        }
        if (preg_match('/^https?:\/\//', $teamLogo)) {
            return $teamLogo;
        }
        return Config::get('services.media.base_url') . $teamLogo;
    }
}
