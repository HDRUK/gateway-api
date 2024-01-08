<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionHasDataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'dataset_id',
        'user_id',
    ];

    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected $table = 'collection_has_datasets';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}
