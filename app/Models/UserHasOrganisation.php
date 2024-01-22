<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHasOrganisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'organisation_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'user_has_organisation';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}