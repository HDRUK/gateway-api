<?php

namespace App\Http\Resources;

use App\Models\Team;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Default HDRUK dataset resource for detailed (show) responses.
 *
 * All virtual properties (counts, relations, versions, linkages) are
 * pre-computed by DatasetService@prepareForShow before arriving here.
 * toArray() is purely structural — it runs no queries.
 *
 * To create a partner-specific variant, extend this class and override
 * toArray(). Register the subclass in config/partners.php under the
 * partner's resource map.
 *
 * Example:
 *   class PartnerXDatasetResource extends DatasetResource
 *   {
 *       public function toArray($request): array
 *       {
 *           return array_merge(parent::toArray($request), [
 *               'custom_id' => $this->some_partner_field,
 *           ]);
 *       }
 *   }
 *
 * @property int $id
 * @property string $pid
 * @property string $status
 * @property string $create_origin
 * @property mixed $created
 * @property mixed $updated
 * @property boolean $is_cohort_discovery
 * @property Team|null $team
 * @property int|null $durs_count
 * @property int|null $publications_count
 * @property int|null $tools_count
 * @property int|null $collections_count
 * @property array $spatialCoverage
 * @property array $durs
 * @property array $publications
 * @property array $named_entities
 * @property array $collections
 * @property array $versions
 * @property array $linkages
 */
class DatasetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'pid'                 => $this->pid,
            'status'              => $this->status,
            'create_origin'       => $this->create_origin,
            'created'             => $this->created,
            'updated'             => $this->updated,
            'is_cohort_discovery' => $this->is_cohort_discovery,

            'team' => $this->when(
                isset($this->team),
                fn () => array_merge($this->team->toArray(), [
                    'has_published_dar_template' => $this->team->has_published_dar_template ?? false,
                ])
            ),

            // Counts — raw SQL, pre-computed by DatasetService
            'durs_count'         => $this->durs_count,
            'publications_count' => $this->publications_count,
            'tools_count'        => $this->tools_count,
            'collections_count'  => $this->collections_count,

            // Relations — resolved via model accessors in DatasetService@prepareForShow
            'spatialCoverage' => $this->spatialCoverage ?? [],
            'durs'            => $this->durs ?? [],
            'publications'    => $this->publications ?? [],
            'named_entities'  => $this->named_entities ?? [],
            'collections'     => $this->collections ?? [],

            // Versioned metadata (with linked dataset versions attached)
            'versions' => $this->versions ?? [],

            // Linkages merged from gateway relations + metadata free-text field
            'linkages' => $this->linkages ?? [],
        ];
    }
}
