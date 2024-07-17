<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class DatasetVersionHasTool extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'dataset_version_id',
        'tool_id',
        'link_type',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'dataset_version_has_tool';

    /**
     * Indicates if the model should be timestamped or not
     * 
     * @var bool
     */
    public $timestamps = false;
}