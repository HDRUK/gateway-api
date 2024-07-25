<?php

namespace App\Models;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatasetVersion extends Model
{
    use HasFactory, SoftDeletes, Prunable;

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
    public function getMetadataAttribute($value): array
    {
        // If the value is already an array, return it directly
        if (is_array($value)) {
            return $value;
        }

        // Decode the value if it's a JSON string
        $decodedValue = json_decode($value, true);

        // If the value is still a JSON string after decoding, decode it again
        if (is_string($decodedValue)) {
            return json_decode($decodedValue, true);
        }

        return $decodedValue;
    }

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
        return $this->belongsToMany(NamedEntities::class, 'dataset_version_has_spatial_coverage');
    }


    /**
     * The tools that belong to the dataset version.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'dataset_version_has_tool');
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

    // Dataset versions where this version is the source
    public function linkedDatasetVersionsAsSource()
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
        )->as('pivot_attributes');
    }

    // Dataset versions where this version is the target
    public function linkedDatasetVersionsAsTarget()
    {
        return $this->belongsToMany(
            DatasetVersion::class, 
            'dataset_version_has_dataset_version',
            'dataset_version_target_id',
            'dataset_version_source_id'
        )->withPivot(
            'dataset_version_source_id', 
            'dataset_version_target_id', 
            'linkage_type', 
            'direct_linkage', 
            'description'
        )->as('pivot_attributes');
    }

     // Define the relationship back to the Dataset
     public function dataset(): BelongsTo
     {
         return $this->belongsTo(Dataset::class, 'dataset_id');
     }

}
