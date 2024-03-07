<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class datasetHasTool extends Model
{
    use HasFactory;


    protected $fillable = [
        'dataset_id',
        'tool_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'dataset_has_tool';

    /**
     * Indicates if the model should be timestamped or not
     * 
     * @var bool
     */
    public $timestamps = false;
}
