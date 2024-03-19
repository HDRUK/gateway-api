<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkedDatasets extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_1_id',
        'dataset_2_id',
        'linkage_type',
        'direct_linkage',
        'description',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'linked_datasets';

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
        return $this->belongsTo(Dataset::class, 'dataset_1_id');
    }

    /**
     * Get the second dataset linked by this linkage.
     */
    public function dataset2()
    {
        return $this->belongsTo(Dataset::class, 'dataset_2_id');
    }
}
