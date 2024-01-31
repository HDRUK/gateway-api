<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DurHasDataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'dur_id',
        'dataset_id',
        'user_id',
        'is_locked',
        'reason',
        'created_at',
        'updated_at',
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
