<?php

namespace App\Models;

use App\Models\Dur;
use App\Models\Tool;
use App\Models\Collection;
use App\Models\Application;
use App\Models\DataVersion;
use App\Models\NamedEntities;
use App\Models\Publication;
use App\Models\SpatialCoverage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dataset extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const ORIGIN_MANUAL = 'MANUAL';
    public const ORIGIN_API = 'API';
    public const ORIGIN_FMA = 'FMA';

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
    public function latestVersion(): DatasetVersion
    {
        $version = DatasetVersion::where('dataset_id', $this->id)
            ->select(['version','id'])
            ->latest('version')
            ->first()
            ->id;
        return DatasetVersion::findOrFail($version);
    }


    /**
     * The very latest metadata via a hasOne relation
     */
    public function latestMetadata(): HasOne
    {
        return $this->hasOne(DatasetVersion::class, 'dataset_id')->withTrashed()->latest('version');
    }

    /**
     * The very latest version number that corresponds to this dataset.
     */
    public function lastMetadataVersionNumber(): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->select('version')->first();
    }

    /**
     * The very last metadata as an array
     */
    public function lastMetadata(): array
    {
        $datasetVersion = DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->select('metadata')->first();
        return  $datasetVersion['metadata'];
    }

    /**
     * Get the latest version's named entities.
     *
     */
    public function getLatestNamedEntities()
    {  
        $entityIds = DatasetVersionHasNamedEntities::where('dataset_version_id', $this->latestVersion()->id)
            ->pluck('named_entities_id');

        return NamedEntities::whereIn('id', $entityIds)->get();
    }

    // Add an accessor for  named entities
    public function getNamedEntitiesAttribute()
    {
        return $this->getLatestNamedEntities();
    }

    /**
     * Get the latest version's spatial coverage.
     */
    public function getLatestSpatialCoverage()
    {
        $entityIds = DatasetVersionHasSpatialCoverage::where('dataset_version_id', $this->latestVersion()->id)
            ->pluck('spatial_coverage_id');

        return SpatialCoverage::whereIn('id', $entityIds)->get();
    }

    // Add an accessor for spatial coverage
    public function getSpatialCoverageAttribute()
    {
        return $this->getLatestSpatialCoverage();
    }

    /**
     * Get the latest version's tools.
     */
    public function getLatestTools()
    {
        $toolIds = DatasetVersionHasTool::where('dataset_version_id', $this->latestVersion()->id)
            ->pluck('tool_id');

        return Tool::whereIn('id', $toolIds)->get();
    }

     // Add an accessor for tools
     public function getToolsAttribute()
     {
         return $this->getLatestTools();
     }

    /**
     * The latest versions collections
     */
    public function getLatestCollections()
    {
        $collectionIds = CollectionHasDatasetVersion::where('dataset_version_id', $this->latestVersion()->id)
            ->pluck('collection_id');

        return Collection::whereIn('id', $collectionIds)->get();
    }

     // Add an accessor for collections
     public function getCollectionsAttribute()
     {
         return $this->getLatestCollections();
     }

    /**
     * The latest versions durs
     */
    public function getLatestDurs()
    {
        $durIds = DurHasDatasetVersion::where('dataset_version_id', $this->latestVersion()->id)
            ->pluck('dur_id');

        return Dur::whereIn('id', $durIds)->get();
    }

     // Add an accessor for durs
     public function getDursAttribute()
     {
         return $this->getLatestDurs();
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
                            ->whereColumn('datasets.id','dataset_versions.dataset_id')
                            ->latest()->limit(1),$direction);
    }

    /**
     * The publications that belong to a dataset.
     */
    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'publication_has_dataset');
    }

    /**
     * The team for the dataset.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}