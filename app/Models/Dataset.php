<?php

namespace App\Models;

use App\Models\Collection;
use App\Models\DataVersion;
use App\Models\NamedEntities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * The very latest version of only the metadata that corresponds to this dataset.
     */
    public function latestMetadata(): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->select('metadata')->first();
    }

    /**
     * The very latest version of a DatasetVersion object that corresponds to this dataset.
     */
    public function latestVersion(): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->first();
    }

    /**
     * The very latest version number that corresponds to this dataset.
     */
    public function lastMetadataVersionNumber(): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->select('version')->first();
    }
}
