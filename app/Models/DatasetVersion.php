<?php

namespace App\Models;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        $normalised = $value;

        if (gettype($normalised) === 'array') {
            $normalised = json_encode($normalised);
        }

        return json_decode(json_decode($normalised, true), true);
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
     * The tools that belong to the dataset version.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'dataset_version_has_tool');
    }

}
