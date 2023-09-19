<?php

namespace App\Models;

use App\Models\Team;
use App\Models\User;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataUseRegister extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'dataset_id',
        'enabled',
        'user_id',
        'ro_crate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Get the dataset in the data use register
     */
    public function dataset(): HasOne
    {
        return $this->hasOne(Dataset::class, 'id', 'dataset_id');
    }

    /**
     * Get the user that owns the data use register
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

}
