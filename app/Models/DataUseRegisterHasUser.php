<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataUseRegisterHasUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_use_register_id',
        'user_id',
    ];

    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected $table = 'data_use_register_has_users';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}
