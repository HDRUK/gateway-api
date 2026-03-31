<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectGrantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
            'pid' => $this->pid,
            'versions' => ProjectGrantVersionResource::collection($this->whenLoaded('versions')),
            'latest_version' => $this->when(
                $this->relationLoaded('latestVersion') && $this->latestVersion,
                fn () => (new ProjectGrantVersionResource($this->latestVersion))->resolve($request)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
