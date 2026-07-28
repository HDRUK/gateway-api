<?php

namespace App\Models;

use App\Models\Base\BaseTypesenseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DataProviderColl extends BaseTypesenseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'enabled',
        'name',
        'summary',
        'img_url',
        'url',
        'service',
    ];

    /**
     * Table associated with this model
     *
     * @var string
    */
    protected $table = 'data_provider_colls';

    /**
     * Indicates if this model is timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Specifically requests that Laravel cast these vars
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'data_provider_coll_has_teams'
        );
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->enabled && $this->deleted_at === null;
    }

    /**
     * Query-level mirror of shouldBeSearchable(), for callers that need a
     * count/filter of indexable rows rather than a per-instance check (e.g.
     * AdminSearchController's eligibleCount). SoftDeletes' own global scope
     * already excludes deleted_at, so only the enabled check is needed here.
     */
    public function scopeIndexEligible(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['teams' => fn ($q) => $q->where('teams.enabled', true)]);
    }

    /**
     * Titles of the latest, non-deleted version of every ACTIVE dataset
     * owned by this network's member teams. Two bulk queries (dataset ids,
     * then their versions) rather than per-dataset lookups, since this runs
     * once per DataProviderColl during indexing.
     */
    private function memberDatasetTitles(array $teamIds): array
    {
        if (empty($teamIds)) {
            return [];
        }

        $datasetIds = Dataset::whereIn('team_id', $teamIds)
            ->where('status', Dataset::STATUS_ACTIVE)
            ->pluck('id');

        if ($datasetIds->isEmpty()) {
            return [];
        }

        return DatasetVersion::whereIn('dataset_id', $datasetIds)
            ->whereNull('deleted_at')
            ->select('dataset_id', 'title', 'version')
            ->get()
            ->groupBy('dataset_id')
            ->map(fn ($versions) => $versions->sortByDesc('version')->first()->title)
            ->filter(fn ($title) => is_string($title) && $title !== '')
            ->values()
            ->all();
    }

    public function toSearchableArray(): array
    {
        $teamIds   = $this->teams->pluck('id')->all();
        $teamNames = $this->teams->pluck('name')->filter()->values()->all();

        return [
            'id'             => (string) $this->id,
            'enabled'        => (int) $this->enabled,
            'name'           => $this->name ?? '',
            'summary'        => $this->summary ?? '',
            'service'        => $this->service ?? '',
            'publisherNames' => $teamNames,
            'datasetTitles'  => $this->memberDatasetTitles($teamIds),
        ];
    }

    public function typesenseSearchParameters(): array
    {
        return [
            'query_by'         => 'name,summary,service',
            'query_by_weights' => '5,4,1',
        ];
    }

    public function typesenseCollectionSchema(): array
    {
        return [
            'name' => $this->searchableAs(),
            'fields' => [
                [ 'name' => 'id',              'type' => 'string', ],
                [ 'name' => 'enabled',         'type' => 'int32', ],
                [ 'name' => 'name',            'type' => 'string', 'infix' => true ],
                [ 'name' => 'summary',         'type' => 'string', 'optional' => true ],
                [ 'name' => 'service',         'type' => 'string', 'optional' => true ],
                [ 'name' => 'publisherNames',  'type' => 'string[]', 'facet' => true, 'optional' => true ],
                [ 'name' => 'datasetTitles',   'type' => 'string[]', 'facet' => true, 'optional' => true ],
            ],
        ];
    }
}
