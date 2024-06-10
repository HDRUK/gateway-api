<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatasetVersionHasDatasetVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_version_1_id',
        'dataset_version_2_id',
        'linkage_type',
        'direct_linkage',
        'description',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dataset_version_has_dataset_version';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the first dataset linked by this linkage.
     */
    public function dataset1()
    {
        return $this->belongsTo(Dataset::class, 'dataset_version_1_id');
    }

    /**
     * Get the second dataset linked by this linkage.
     */
    
    public function dataset2()
    {
        return $this->belongsTo(Dataset::class, 'dataset_version_2_id');
    }
}
