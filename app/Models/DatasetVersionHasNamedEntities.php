<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DatasetVersionHasNamedEntities extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'dataset_version_id',
        'named_entities_id',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dataset_version_has_named_entities';

    /**
     * Indicates if the model should be timestamped or not
     *
     * @var bool
     */
    public $timestamps = false;
}
