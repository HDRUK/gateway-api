<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\DatasetVersionObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *   schema="DatasetVersion",
 *   description="A versioned snapshot of dataset metadata in GWDM format",
 *   @OA\Property(property="id", type="integer", example=101),
 *   @OA\Property(property="dataset_id", type="integer", example=1),
 *   @OA\Property(property="version", type="integer", example=3),
 *   @OA\Property(property="title", type="string", nullable=true, example="UK Biobank"),
 *   @OA\Property(property="short_title", type="string", nullable=true, example="UKB"),
 *   @OA\Property(
 *     property="metadata",
 *     type="object",
 *     nullable=true,
 *     description="Full GWDM-format metadata document for this version"
 *   ),
 *   @OA\Property(
 *     property="patch",
 *     type="array",
 *     nullable=true,
 *     description="RFC 6902 JSON Patch array used to reconstruct this version from the previous snapshot. Null for full snapshots (v1 and every 10th version).",
 *     @OA\Items(type="object")
 *   ),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
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
        'patch'    => 'array',
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
        'patch',
        'version',
        // title and short_title are no longer GENERATED columns (see migration
        // 2026_03_11_133601); they must be populated explicitly by the service layer
        // from the reconstructed GWDM metadata at write time.
        'title',
        'short_title',
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
        return $query->where('title', 'LIKE', "%{$filterTitle}%");
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
        )->selectRaw("dataset_versions.id, dataset_versions.dataset_id,
        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.title')) as title,
        short_title as shortTitle");
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class, 'dataset_id', 'id')
            ->where('status', 'ACTIVE')
            ->select(['id', 'status']);
    }
}
