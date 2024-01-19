<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataUseRegisterHasDataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_use_register_id',
        'dataset_id',
        'user_id',
    ];

    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected $table = 'data_use_register_has_datasets';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}
