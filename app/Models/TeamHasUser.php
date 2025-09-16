<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamHasUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'team_has_users';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'team_id',
    ];

    // public function permissions(): BelongsToMany
    // {
    //     return $this->belongsToMany(Permission::class, 'team_user_has_permissions');
    // }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'team_user_has_roles');
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'team_user_has_notifications');
    }
}
