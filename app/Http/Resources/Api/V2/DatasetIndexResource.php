<?php

namespace App\Http\Resources\Api\V2;

use Config;
use App\Models\DatasetVersion;
use App\Services\Gwdm\SourceSchema;
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
 *
 * @property int $id
 * @property string $pid
 * @property string $status
 * @property string $create_origin
 * @property mixed $created
 * @property mixed $updated
 * @property string $partner_context
 * @property DatasetVersion|null $latestMetadata
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
            'partner_context' => $this->partner_context,

            // Listings never translate, so latest_metadata is always native GWDM.
            // The version is only known when latestMetadata was loaded (with_metadata).
            'schema' => [
                'model'   => Config::get('metadata.GWDM.name'),
                'version' => $this->resource->relationLoaded('latestMetadata')
                    ? $this->latestMetadata?->gwdm_version
                    : null,
            ],

            // The schema the latest version was submitted in, or null.
            'source_schema' => $this->resource->relationLoaded('latestMetadata') && $this->latestMetadata
                ? SourceSchema::fromColumns(
                    $this->latestMetadata->only(['source_schema_model', 'source_schema_version'])
                )->toArray()
                : null,

            'latest_metadata' => $this->whenLoaded('latestMetadata'),
        ];
    }
}
