<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamUserPermission extends Model
{
    use HasFactory;

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'team_user_permissions';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'team_id', 'user_id', 'permission_id',
    ];
}
