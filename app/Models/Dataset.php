<?php

namespace App\Models;

use DB;
use App\Models\Traits\EntityCounter;
use App\Observers\DatasetObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property array $linkages
 *
 * @OA\Schema(
 *   schema="Dataset",
 *   description="A dataset record managed by the Gateway",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="user_id", type="integer", nullable=true, example=42),
 *   @OA\Property(property="team_id", type="integer", nullable=true, example=7),
 *   @OA\Property(property="pid", type="string", nullable=true, example="d4b3c2a1-0000-0000-0000-000000000000"),
 *   @OA\Property(property="datasetid", type="string", nullable=true, example="some-legacy-id"),
 *   @OA\Property(property="version", type="integer", nullable=true, example=3),
 *   @OA\Property(
 *     property="status",
 *     type="string",
 *     enum={"ACTIVE","DRAFT","ARCHIVED"},
 *     example="ACTIVE"
 *   ),
 *   @OA\Property(
 *     property="create_origin",
 *     type="string",
 *     enum={"MANUAL","API","GMI"},
 *     nullable=true,
 *     example="MANUAL"
 *   ),
 *   @OA\Property(property="is_cohort_discovery", type="boolean", example=false),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
#[ObservedBy(DatasetObserver::class)]
class Dataset extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;
    use EntityCounter;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const ORIGIN_MANUAL = 'MANUAL';
    public const ORIGIN_API = 'API';
    public const ORIGIN_GMI = 'GMI';

    public string $prevStatus = '';

    /**
     * Cached version IDs to avoid repeated `versions()->pluck('id')` queries
     * when multiple accessors are called in the same request cycle.
     * Set this before calling allActiveTools/allActiveDurs/etc. in a loop.
     */
    public ?array $cachedVersionIds = null;

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'datasets';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'team_id',
        'mongo_object_id',
        'mongo_id',
        'mongo_pid',
        'datasetid',
        'metadata',
        'created',
        'updated',
        'submitted',
        'pid',
        'version',
        'create_origin',
        'status',
        'is_cohort_discovery',
    ];

    protected $casts = [
        'is_cohort_discovery' => 'boolean',
    ];

    protected static array $sortableColumns = [
        'created',
        'updated',
        // we can also sort by 'metadata.summary.title', but treat this
        // separately within scopeApplySorting() rather than confuse the meaning of `$sortableColumns`
    ];

    protected static array $countableColumns = [
        'status',
    ];

    public function getToolsAttribute()
    {
        return $this->attributes['tools'] ?? collect();
    }

    public function getToolsCountAttribute()
    {
        return $this->attributes['tools_count'] ?? 0;
    }

    public function getDursAttribute()
    {
        return $this->attributes['durs'] ?? collect();
    }

    public function getDursCountAttribute()
    {
        return $this->attributes['durs_count'] ?? 0;
    }

    public function getCollectionsAttribute()
    {
        return $this->attributes['collections'] ?? collect();
    }

    public function getCollectionsCountAttribute()
    {
        return $this->attributes['collections_count'] ?? 0;
    }

    public function getPublicationsAttribute()
    {
        return $this->attributes['publications'] ?? collect();
    }

    public function getPublicationsCountAttribute()
    {
        return $this->attributes['publications_count'] ?? 0;
    }

    public function getSpatialCoverageAttribute()
    {
        return $this->attributes['spatialCoverage'] ?? 0;
    }

    public function getNamedEntitiesAttribute()
    {
        return $this->attributes['named_entities'] ?? 0;
    }

    /**
     * @return HasMany<DatasetVersion, $this>
     *
     * The version history of metadata that respond to this dataset.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DatasetVersion::class, 'dataset_id');
    }

    /**
     * The very latest version of a DatasetVersion object that corresponds to this dataset.
     **/
    public function latestVersion(?array $fields = null): DatasetVersion | null
    {
        $version = DatasetVersion::where('dataset_id', $this->id)
            ->select(['version','id'])
            ->latest('version')
            ->first();

        if (!$version) {
            return null;
        }

        return DatasetVersion::when(
            $fields,
            function ($query, $fields) {
                return $query->select($fields);
            }
        )->findOrFail($version->id);
    }

    public function latestVersionID(int $datasetId): null|int
    {
        $result = DB::select(
            '
                SELECT 
                    dv.id,
                    dv.version
                FROM dataset_versions dv
                WHERE
                    dv.dataset_id = :dataset_id
                ORDER BY
                    version DESC
                LIMIT 1
            ',
            [
                'dataset_id' => $datasetId,
            ]
        );

        if (count($result) > 0) {
            return $result[0]->id;
        }

        return null;
    }

    /** @return HasOne<DatasetVersion, $this> */
    public function latestMetadata(): HasOne
    {
        return $this->hasOne(DatasetVersion::class, 'dataset_id')
            ->latestOfMany('version');
    }

    /**
     * The very latest version number that corresponds to this dataset.
     */
    public function lastMetadataVersionNumber(): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->orderBy('version', 'desc')->select('version')->first();
    }

    /**
     * The very last metadata as an array
     */
    public function lastMetadata(): array
    {
        $version = DatasetVersion::where('dataset_id', $this->id)
            ->select(['version','id'])
            ->orderBy('version', 'desc')
            ->first()
            ->id;
        $datasetVersion = DatasetVersion::findOrFail($version)->toArray();
        return $datasetVersion['metadata'];
    }


    /**
     * Helper function to use JSON functions to search by title within metadata.
     */
    public function searchByTitle(string $title): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->whereRaw(
                "
                LOWER(JSON_EXTRACT(metadata, '$.metadata.summary.title')) LIKE LOWER('%$title%')
                "
            )->latest('version')->first();
    }

    /**
     * Helper function to grab dataset title from latest metadata.
     */
    public function getTitle(): string
    {
        $version = DatasetVersion::where('dataset_id', $this->id)
            ->select(['version','id'])
            ->orderBy('version', 'desc')
            ->first()
            ->id;
        $datasetVersion = DatasetVersion::findOrFail($version)->toArray();

        return $datasetVersion['metadata']['metadata']['summary']['title'];
    }

    /**
     * Order by raw metadata extract
     */
    public function scopeOrderByMetadata(Builder $query, string $field, string $direction): Builder
    {
        return $query->orderBy(DatasetVersion::selectRaw("JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.".$field."')")
            ->whereColumn('datasets.id', 'dataset_versions.dataset_id')
            ->latest()->limit(1), $direction);
    }

    /**
     * Apply sorting
     */
    public function scopeApplySorting($query): mixed
    {
        // This function closely mirrors the SortManager Trait, but is separate to handle the special case of metadata.summary.title.

        $input = \request()->all();
        // If no sort option passed, then always default to the first element
        // of our sortableColumns array on the model
        $sort = isset($input['sort']) ? $input['sort'] : static::$sortableColumns[0] . ':desc';

        $tmp = explode(':', $sort);
        $field = strtolower($tmp[0]);

        if (isset(static::$sortableColumns)
            && (!in_array(strtolower($field), static::$sortableColumns) && (strtolower($field) !== 'metadata.summary.title'))) {
            throw new \InvalidArgumentException('field ' . $field . ' is not sortable.');
        }

        $direction = (count($tmp) > 1) ? strtolower($tmp[1]) : 'desc';
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException('invalid sort direction ' . $direction);
        }

        if ($field !== 'metadata.summary.title') {
            return $query->orderBy($field, $direction);
        }

        return $query->orderBy(DatasetVersion::selectRaw("JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.".$field."')")
            ->whereColumn('datasets.id', 'dataset_versions.dataset_id')
            ->latest()->limit(1), $direction);
    }

    /**
     * @return BelongsTo<Team, $this>
     *
     * The team for the dataset.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    // Accessor for all named entities
    public function getAllNamedEntitiesAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            DatasetVersionHasNamedEntities::class,
            NamedEntities::class,
            'named_entities_id'
        );
    }

    // Accessor for all spatial coverages
    public function getAllSpatialCoveragesAttribute()
    {
        return $this->getSpatialCoverageViaVersions(
            SpatialCoverage::class,
            'dataset_version_has_spatial_coverage',
            'spatial_coverage_id'
        );
    }

    private function getSpatialCoverageViaVersions(
        string $targetClass,
        string $linkageTable,
        string $foreignKey,
    ): array {
        $targetTable = (new $targetClass())->getTable();

        $results = DB::select("
            SELECT {$targetTable}.*, {$linkageTable}.dataset_version_id
            FROM {$targetTable}
            INNER JOIN {$linkageTable} ON {$targetTable}.id = {$linkageTable}.{$foreignKey}
            INNER JOIN dataset_versions ON dataset_versions.id = {$linkageTable}.dataset_version_id
            WHERE dataset_versions.dataset_id = ?
        ", [$this->id]);

        return collect($results)
            ->groupBy('id')
            ->map(function ($rows) {
                $entity = (array) $rows->first();
                $entity['dataset_version_ids'] = $rows->pluck('dataset_version_id')->toArray();
                return $entity;
            })
            ->values()
            ->toArray();
    }

    // Accessor for all tools
    public function getAllToolsAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            DatasetVersionHasTool::class,
            Tool::class,
            'tool_id'
        );
    }

    // Accessor for all ACTIVE tools
    public function getAllActiveToolsAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            DatasetVersionHasTool::class,
            Tool::class,
            'tool_id',
            false,
            true
        );
    }

    // Accessor for all collections
    public function getAllCollectionsAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            CollectionHasDatasetVersion::class,
            Collection::class,
            'collection_id'
        );
    }

    // Accessor for all ACTIVE collections
    public function getAllActiveCollectionsAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            CollectionHasDatasetVersion::class,
            Collection::class,
            'collection_id',
            false,
            true
        );
    }

    // Accessor for all durs
    public function getAllDursAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            DurHasDatasetVersion::class,
            Dur::class,
            'dur_id'
        );
    }

    // Accessor for all ACTIVE durs
    public function getAllActiveDursAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            DurHasDatasetVersion::class,
            Dur::class,
            'dur_id',
            false,
            true
        );
    }

    // Accessor for all publications
    public function getAllPublicationsAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            PublicationHasDatasetVersion::class,
            Publication::class,
            'publication_id',
            true
        );
    }

    // Accessor for all ACTIVE publications
    public function getAllActivePublicationsAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            PublicationHasDatasetVersion::class,
            Publication::class,
            'publication_id',
            true,
            true
        );
    }

    /**
     * Helper function to get stuff linked by datasetVersionHasX
     */
    public function getRelationsViaDatasetVersion($linkageTable, $targetTable, $foreignTableId, $includeIntermediate = false, $filterActive = false)
    {
        // Get the dataset version IDs — reuse the cached copy when available
        // so repeated accessor calls within the same request don't re-query.
        $versionIds = $this->cachedVersionIds
            ?? $this->versions()->pluck('id')->toArray();

        // Step 2: Use the version IDs to find all related entityIDs through the linkage table
        $linkageRecords = $linkageTable::whereIn('dataset_version_id', $versionIds)
            ->when(
                $includeIntermediate,
                function ($query) {
                    return $query->get();
                },
                function ($query) use ($foreignTableId) {
                    return $query->get([$foreignTableId, 'dataset_version_id']);
                }
            );

        $entityIds = $linkageRecords->pluck($foreignTableId)->unique()->toArray();

        // Step 3: Retrieve all entities using the collected entities IDs
        $entities = $targetTable::whereIn('id', $entityIds)
            ->when($filterActive, function ($query) {
                return $query->where('status', self::STATUS_ACTIVE);
            })
            ->get();

        // Pre-group linkage records by entity ID so the loop below is O(n)
        // rather than O(n2). Without this, each iteration does a full Collection
        // scan (Collection::where), which was measured at ~620ms for 82 DURs
        // and ~770ms for 92 named entities.
        $linkageByEntityId = $linkageRecords->groupBy($foreignTableId);

        foreach ($entities as $entity) {
            $filteredLinkage = $linkageByEntityId->get($entity->id) ?? collect();

            if ($includeIntermediate) {
                $entity->setAttribute('dataset_versions', $filteredLinkage->values()->toArray());
            } else {
                $entity->setAttribute('dataset_version_ids', $filteredLinkage->pluck('dataset_version_id')->toArray());
            }
        }

        // Return the collection of entities with injected dataset version IDs
        return $entities->toArray();
    }
}
