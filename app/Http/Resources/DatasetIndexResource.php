<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight dataset resource for paginated index responses.
 *
 * Excludes all resolved relations and linkages — the index only
 * needs the parent record fields and optionally the latest metadata.
 * This keeps the listing query fast and the payload small.
 *
 * Partners that need a different index shape should extend this class
 * and register it in config/partners.php.
 */
class DatasetIndexResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'pid'           => $this->pid,
            'status'        => $this->status,
            'create_origin' => $this->create_origin,
            'created'       => $this->created,
            'updated'       => $this->updated,
            'latestMetadata' => $this->when(
                $this->relationLoaded('latestMetadata'),
                $this->latestMetadata
            ),
        ];
    }
}
