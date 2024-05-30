<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatasetVersionHasTool extends Model
{
    use HasFactory;


    protected $fillable = [
        'dataset_version_id',
        'tool_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'dataset_version_has_tools';

    /**
     * Indicates if the model should be timestamped or not
     * 
     * @var bool
     */
    public $timestamps = false;
}
