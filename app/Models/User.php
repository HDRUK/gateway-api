<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Team;
use App\Models\Tool;
use App\Models\Permission;
// use Laravel\Sanctum\HasApiTokens;
use App\Models\Application;
use App\Http\Traits\WithJwtUser;
use Laravel\Passport\HasApiTokens;
use App\Http\Enums\UserPreferredEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;
    use WithJwtUser;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'email',
        'secondary_email',
        'preferred_email',
        'password',
        'provider',
        'sector_id',
        'organisation',
        'bio',
        'domain',
        'link',
        'orcid',
        'contact_feedback',
        'contact_news',
        'mongo_id',
        'mongo_object_id',
        'is_admin',
        'terms',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'terms' => 'boolean',
    ];

    protected $appends = ['rquestroles'];

    public function getRquestrolesAttribute()
    {
        $id = $this->id;

        $cohortRequest = CohortRequest::where([
            'user_id' => $id,
            'request_status' => 'APPROVED',
        ])->first();

        if (!$cohortRequest) {
            return [];
        }

        $cohortRequestRoleIds = CohortRequestHasPermission::where([
            'cohort_request_id' => $cohortRequest->id
        ])->pluck('permission_id')->toArray();

        $cohortRequestRoles = Permission::whereIn('id', $cohortRequestRoleIds)->pluck('name')->toArray();

        return $cohortRequestRoles;
    }

    /**
     * Get the tool that owns the user
     */
    public function tool(): HasOne
    {
        return $this->hasOne(Tool::class, 'user_id', 'id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_has_users')
            ->withPivot('team_id', 'id')
            ->orderBy('team_has_users.team_id');  
    }    

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'user_has_notifications');
    }

    public function review(): HasMany
    {
        return $this->hasMany(Review::class);
    }
    
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_has_roles');
    }

    public function cohortRequests(): HasMany
    {
        return $this->hasMany(CohortRequest::class);
    }
    
}
