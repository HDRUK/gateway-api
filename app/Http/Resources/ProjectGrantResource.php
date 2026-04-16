<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ProjectGrant;

class ProjectGrantResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ProjectGrant $grant */
        $grant = $this->resource;

        return [
            'id' => $grant->id,
            'user_id' => $grant->user_id,
            'team_id' => $grant->team_id,
            'pid' => $grant->pid,
            'datasets' => $this->whenLoaded('datasets'),
            'versions' => ProjectGrantVersionResource::collection($this->whenLoaded('versions')),
            'latest_version' => $this->when(
                $grant->relationLoaded('latestVersion') && $grant->latestVersion,
                fn () => (new ProjectGrantVersionResource($grant->latestVersion))->resolve($request)
            ),
            'created_at' => $grant->created_at,
            'updated_at' => $grant->updated_at,
            'deleted_at' => $grant->deleted_at,
        ];
    }
}
