<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ProjectGrant;

class ProjectGrantIndexResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ProjectGrant $grant */
        $grant = $this->resource;

        $latest = null;
        if ($grant->relationLoaded('versions') && $grant->versions->isNotEmpty()) {
            $latest = $grant->versions->sortByDesc('version')->first();
        } elseif ($grant->relationLoaded('latestVersion') && $grant->latestVersion) {
            $latest = $grant->latestVersion;
        }

        return [
            'id' => $grant->id,
            'user_id' => $grant->user_id,
            'team_id' => $grant->team_id,
            'pid' => $grant->pid,
            'latest_version' => $latest ? (new ProjectGrantVersionResource($latest))->resolve($request) : null,
            'created_at' => $grant->created_at,
            'updated_at' => $grant->updated_at,
        ];
    }
}
