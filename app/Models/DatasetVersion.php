<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\DatasetVersionObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(DatasetVersionObserver::class)]
class DatasetVersion extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'dataset_versions';

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'dataset_id',
        'metadata',
        'version',
    ];

    /**
     * Get and Set the metadata.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function metadata(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $response = json_decode($value, true);
                if (is_string($response)) {
                    $response = json_decode($response, true);
                }
                return $response;
            },
            set: fn ($value) => is_array($value) ? json_encode($value) : $value,
        );
    }

    /**
     * Accessor for the metadata field to convert json string to
     * php array for inclusion in json response object. Weirdly
     * the $casts of metadata to array _was_ failing. Possibly due
     * to the encoding of the string being added to the db field.
     * Needs further investigation as this is just a workaround.
     *
     * @param $value The original value prior to pre-processing
     *
     * @return array The json metadata string as an array
     */
    // public function getMetadataAttribute($value): array
    // {
    //     // If the value is already an array, return it directly
    //     if (is_array($value)) {
    //         return $value;
    //     }

    //     // Decode the value if it's a JSON string
    //     $decodedValue = json_decode($value, true);

    //     // If the value is still a JSON string after decoding, decode it again
    //     if (is_string($decodedValue)) {
    //         return json_decode($decodedValue, true);
    //     }

    //     return $decodedValue;
    // }

    /**
    * Scope a query to filter on metadata summary title
    *
    * @param Builder $query
    * @param string $filterTitle
    * @return Builder
    */
    public function scopeFilterTitle(Builder $query, string $filterTitle): Builder
    {
        return $query->whereRaw(
            "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.title')) LIKE LOWER(?)",
            ["%$filterTitle%"]
        );
    }

    /**
     *  Named entities that belong to the dataset version.
     */
    public function namedEntities(): BelongsToMany
    {
        return $this->belongsToMany(NamedEntities::class, 'dataset_version_has_named_entities');
    }

    /**
     *  Spatial coverage that belong to the dataset version.
     */
    public function spatialCoverage(): BelongsToMany
    {
        return $this->belongsToMany(SpatialCoverage::class, 'dataset_version_has_spatial_coverage');
    }


    /**
     * The tools that belong to the dataset version.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'dataset_version_has_tool', 'dataset_version_id', 'tool_id');
    }

    /**
     * The durs that belong to the dataset version.
     */
    public function durHasDatasetVersions(): HasMany
    {
        return $this->hasMany(DurHasDatasetVersion::class);
    }

    /**
     * The publications that belong to the dataset version.
     */
    public function publicationHasDatasetVersions(): HasMany
    {
        return $this->hasMany(PublicationHasDatasetVersion::class);
    }

    /**
     * The collections that belong to the dataset version.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(CollectionHasDatasetVersion::class);
    }

    /**
     * The durs that belong to the dataset version.
     */
    public function durs(): HasMany
    {
        return $this->hasMany(DurHasDatasetVersion::class);
    }

    /**
     * The durs that belong to the dataset version.
     */
    public function publications(): HasMany
    {
        return $this->hasMany(PublicationHasDatasetVersion::class);
    }

    /**
     * The dataset versions that belong to the dataset version.
     */
    public function linkedDatasetVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            DatasetVersion::class,
            'dataset_version_has_dataset_version',
            'dataset_version_source_id',
            'dataset_version_target_id'
        )->withPivot(
            'dataset_version_source_id',
            'dataset_version_target_id',
            'linkage_type',
            'direct_linkage',
            'description'
        );
    }

    /**
    * The reduced dataset versions that belong to the dataset version, the above linkedDatasetVersions
    * is used in a few places, if in infuture we discover that we only ever need to use the below instead,
    * we can easily switch. - Jamie B
    */
    public function reducedLinkedDatasetVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            DatasetVersion::class,
            'dataset_version_has_dataset_version',
            'dataset_version_source_id',
            'dataset_version_target_id'
        )->withPivot(
            'dataset_version_source_id',
            'dataset_version_target_id',
            'linkage_type',
        )->selectRaw("dataset_versions.id,
        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.title')) as title,
        short_title as shortTitle");
    }

}
