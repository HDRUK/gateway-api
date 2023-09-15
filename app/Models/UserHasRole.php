<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHasRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'user_has_roles';

    /**
     * Indicates if the model should be timestamped or not
     * 
     * @var bool
     */
    public $timestamps = false;
}
