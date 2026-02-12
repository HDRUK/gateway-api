<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'name',
        'application',
    ];

    public function teamHasUsers(): BelongsToMany
    {
        return $this->belongsToMany(TeamHasUser::class, 'team_user_has_permissions');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_has_permissions');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(TeamHasUser::class, 'role_has_permissions');
    }

    public function cohortRequests(): BelongsToMany
    {
        return $this->belongsToMany(TeamHasUser::class, 'cohort_request_has_permissions');
    }
}
