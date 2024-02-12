<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatasetHasSpatialCoverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'spatial_coverage_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'dataset_has_spatial_coverage';

    /**
     * Indicates if the model should be timestamped or not
     * 
     * @var bool
     */
    public $timestamps = false;
}