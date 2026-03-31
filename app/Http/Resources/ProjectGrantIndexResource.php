<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectGrantIndexResource extends JsonResource
{
    public function toArray($request): array
    {
        $latest = null;
        if ($this->relationLoaded('versions') && $this->versions->isNotEmpty()) {
            $latest = $this->versions->sortByDesc('version')->first();
        } elseif ($this->relationLoaded('latestVersion') && $this->latestVersion) {
            $latest = $this->latestVersion;
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
            'pid' => $this->pid,
            'latest_version' => $latest ? (new ProjectGrantVersionResource($latest))->resolve($request) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
