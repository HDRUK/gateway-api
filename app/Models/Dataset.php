<?php

namespace App\Models;

use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dataset extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const ORIGIN_MANUAL = 'MANUAL';
    public const ORIGIN_API = 'API';
    public const ORIGIN_GMI = 'GMI';

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


    /**
     * The version history of metadata that respond to this dataset.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DatasetVersion::class, 'dataset_id');
    }

    /**
     * The very latest version of a DatasetVersion object that corresponds to this dataset.
     **/
    public function latestVersion(?array $fields = null): DatasetVersion
    {
        $version = DatasetVersion::where('dataset_id', $this->id)
            ->select(['version','id'])
            ->latest('version')
            ->first()
            ->id;
        return DatasetVersion::when(
            $fields,
            function ($query, $fields) {
                return $query->select($fields);
            }
        )->findOrFail($version);
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

    public function latestMetadata(): HasOne
    {
        return $this->hasOne(DatasetVersion::class, 'dataset_id')->withTrashed();
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
     * Order by raw metadata extract
     */
    public function scopeOrderByMetadata(Builder $query, string $field, string $direction): Builder
    {
        return $query->orderBy(DatasetVersion::selectRaw("JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.".$field."')")
            ->whereColumn('datasets.id', 'dataset_versions.dataset_id')
            ->latest()->limit(1), $direction);
    }

    /**
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
        return $this->getRelationsViaDatasetVersion(
            DatasetVersionHasSpatialCoverage::class,
            SpatialCoverage::class,
            'spatial_coverage_id'
        );
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

    // Accessor for all collections
    public function getAllCollectionsAttribute()
    {
        return $this->getRelationsViaDatasetVersion(
            CollectionHasDatasetVersion::class,
            Collection::class,
            'collection_id'
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

    /**
     * Helper function to get stuff linked by datasetVersionHasX
     */
    public function getRelationsViaDatasetVersion($linkageTable, $targetTable, $foreignTableId, $includeIntermediate = false)
    {
        // Step 1: Get the dataset version IDs
        $versionIds = $this->versions()->pluck('id')->toArray();

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
        $entities = $targetTable::whereIn('id', $entityIds)->get();

        // Iterate through each entity and add associated dataset versions
        foreach ($entities as $entity) {
            // Retrieve dataset version IDs associated with the current entity

            $filteredLinkage = $linkageRecords->where($foreignTableId, $entity->id);

            if($includeIntermediate) {
                $entity->setAttribute('dataset_versions', $filteredLinkage->values()->toArray());
            } else {
                // Extract dataset version IDs and link types associated with the current entity
                $datasetVersionIds = $filteredLinkage->pluck('dataset_version_id')->toArray();
                // Add associated dataset versions to the entity object
                $entity->setAttribute('dataset_version_ids', $datasetVersionIds);
            }

        }

        // Return the collection of entities with injected dataset version IDs
        return $entities->toArray();
    }
}
