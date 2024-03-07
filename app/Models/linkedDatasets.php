<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkedDatasets extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'linked_datasets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'dataset_1_id',
        'dataset_2_id',
        'linkage_type',
        'direct_linkage',
        'description',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    // Define relationships here if needed
    // For example, to define a relationship to the Dataset model

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
