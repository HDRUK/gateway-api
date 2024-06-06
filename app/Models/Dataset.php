<?php

namespace App\Models;

use App\Models\Application;
use App\Models\Collection;
use App\Models\DataVersion;
use App\Models\Dur;
use App\Models\NamedEntities;
use App\Models\Publication;
use App\Models\SpatialCoverage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

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
     * The named_entities that belong to the dataset.
     */
    public function namedEntities(): BelongsToMany
    {
        return $this->belongsToMany(NamedEntities::class, 'dataset_has_named_entities');
    }

    /**
     * The version history of metadata that respond to this dataset.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DatasetVersion::class, 'dataset_id');
    }

    /**
     * The collections that the dataset belongs to.
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_has_datasets');
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
     * The very latest version of a DatasetVersion object that corresponds to this dataset.
     **/
    public function latestVersion(): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->first();
    }

    /**
     * The very latest metadata via a hasOne relation
     */
    public function latestMetadata(): HasOne
    {
        return $this->hasOne(DatasetVersion::class, 'dataset_id')->latest('version');
    }

    /**
     * The very latest version number that corresponds to this dataset.
     */
    public function lastMetadataVersionNumber(): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->select('version')->first();
    }

    public function lastMetadata(): array
    {
        $datasetVersion = DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->select('metadata')->first();
        return  $datasetVersion['metadata'];
    }
    
    /**
     * The spatial coverage that belong to the dataset.
     */
    public function spatialCoverage(): BelongsToMany
    {
        return $this->belongsToMany(SpatialCoverage::class, 'dataset_has_spatial_coverage');
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

    public function durs(): BelongsToMany
    {
        return $this->belongsToMany(Dur::class, 'dur_has_datasets');
    }

    /**
     * The publications that belong to a dataset.
     */
    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'publication_has_dataset');
    }
}