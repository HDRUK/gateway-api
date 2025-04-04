<?php

namespace App\Models;

use App\Http\Traits\DatasetFetch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpatialCoverage extends Model
{
    use HasFactory;
    use DatasetFetch;

    protected $fillable = [
        'region',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    public $table = 'spatial_coverage';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the region of this spatial coverage
     *
     * @var string
     */
    private $region = '';

    /**
     * Whether or not this region is enabled
     *
     * @var boolean
     */
    private $enabled = false;

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getDatasetsViaDatasetVersion(
            new DatasetVersionHasSpatialCoverage(),
            'spatial_coverage_id'
        );
    }

    /**
     * Retrieve versions associated with this spatial coverage.
     */
    public function versions()
    {
        return $this->belongsToMany(DatasetVersion::class, 'dataset_version_has_spatial_coverage', 'spatial_coverage_id', 'dataset_version_id');
    }
}
