<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataUseRegisterHasDatasets extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

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
