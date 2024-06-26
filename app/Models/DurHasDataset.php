<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DurHasDataset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dur_id',
        'dataset_id',
        'user_id',
        'application_id',
        'is_locked',
        'reason',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected $table = 'dur_has_datasets';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}