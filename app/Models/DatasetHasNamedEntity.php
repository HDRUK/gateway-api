<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatasetHasNamedEntity extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'named_entity_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'dataset_has_named_entities';

    /**
     * Indicates if the model should be timestamped or not
     * 
     * @var bool
     */
    public $timestamps = false;
}
