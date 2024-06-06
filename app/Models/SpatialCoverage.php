<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SpatialCoverage extends Model
{
    use HasFactory;

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

    /**
     * The datasets that belong to the spatial coverage.
     */
    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'dataset_has_spatial_coverage');
    }
}
