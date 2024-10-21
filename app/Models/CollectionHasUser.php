<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionHasUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'collection_has_users';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'collection_id',
        'user_id',
        'role',
    ];
}
