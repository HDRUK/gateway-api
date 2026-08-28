<?php

namespace App\Models;

// use Laravel\Sanctum\HasApiTokens;
use App\Http\Traits\WithJwtUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * @OA\Schema(
 *   schema="User",
 *   description="A registered Gateway user",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", nullable=true, example="Jane Smith"),
 *   @OA\Property(property="firstname", type="string", nullable=true, example="Jane"),
 *   @OA\Property(property="lastname", type="string", nullable=true, example="Smith"),
 *   @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
 *   @OA\Property(property="secondary_email", type="string", format="email", nullable=true),
 *   @OA\Property(property="preferred_email", type="string", enum={"primary","secondary"}, nullable=true, example="primary"),
 *   @OA\Property(property="provider", type="string", nullable=true, example="google"),
 *   @OA\Property(property="sector_id", type="integer", nullable=true),
 *   @OA\Property(property="organisation", type="string", nullable=true, example="University of Cambridge"),
 *   @OA\Property(property="bio", type="string", nullable=true),
 *   @OA\Property(property="domain", type="string", nullable=true),
 *   @OA\Property(property="link", type="string", format="uri", nullable=true),
 *   @OA\Property(property="orcid", type="string", nullable=true, example="0000-0002-1825-0097"),
 *   @OA\Property(property="contact_feedback", type="boolean", nullable=true, example=false),
 *   @OA\Property(property="contact_news", type="boolean", nullable=true, example=false),
 *   @OA\Property(property="is_admin", type="boolean", example=false),
 *   @OA\Property(property="terms", type="boolean", example=true),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use WithJwtUser;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'email',
        'secondary_email',
        'secondary_email_verified_at',
        'preferred_email',
        'password',
        'provider',
        'providerid',
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
        'is_nhse_sde_approval',
        'registry_digital_identifier',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'providerid',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'terms' => 'boolean',
        'is_nhse_sde_approval' => 'boolean',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    protected $appends = ['rquestroles', 'cohort_discovery_roles', 'cohort_discovery_nhs_sde'];

    /**
     * Instance-scoped cache populated by preloadCohortDataForUsers() to avoid
     * per-user queries when serializing many users at once. Instance-level
     * (not static) so it carries no state across requests under Octane.
     */
    private ?array $cohortRoleCache = null;

    private ?bool $cohortNhsSdeCache = null;

    /**
     * Batch-load cohort request roles and NHS SDE approval for a collection of
     * users, so serializing them (which auto-computes the $appends below)
     * doesn't issue per-user queries. Call before ->toArray()/response.
     */
    public static function preloadCohortDataForUsers(\Illuminate\Support\Collection $users): void
    {
        $userIds = $users->pluck('id')->unique()->values()->all();

        if (empty($userIds)) {
            return;
        }

        $accessGrantingCohortRequestByUser = CohortRequest::whereIn('user_id', $userIds)
            ->whereIn('request_status', CohortRequest::ACCESS_GRANTING_STATUSES)
            ->get()
            ->filter(fn ($cohortRequest) => CohortRequest::grantsAccess($cohortRequest))
            ->groupBy('user_id')
            ->map(fn ($requests) => $requests->first());

        $cohortRequestIds = $accessGrantingCohortRequestByUser->pluck('id')->all();

        $permissionIdsByCohortRequest = CohortRequestHasPermission::whereIn('cohort_request_id', $cohortRequestIds)
            ->get()
            ->groupBy('cohort_request_id');

        $allPermissionIds = $permissionIdsByCohortRequest->flatten()->pluck('permission_id')->unique()->values()->all();

        $permissionNamesById = Permission::whereIn('id', $allPermissionIds)->pluck('name', 'id');

        $nhsSdeApprovedUserIds = array_flip(
            CohortRequest::whereIn('user_id', $userIds)
                ->whereIn('request_status', CohortRequest::ACCESS_GRANTING_STATUSES)
                ->where('nhse_sde_request_status', 'APPROVED')
                ->whereNull('nhse_sde_request_expire_at')
                ->get()
                ->filter(fn ($cohortRequest) => CohortRequest::grantsAccess($cohortRequest))
                ->pluck('user_id')
                ->unique()
                ->all()
        );

        foreach ($users as $user) {
            $cohortRequest = $accessGrantingCohortRequestByUser->get($user->id);

            if ($cohortRequest === null) {
                $user->cohortRoleCache = [];
            } else {
                $user->cohortRoleCache = $permissionIdsByCohortRequest->get($cohortRequest->id, collect())
                    ->pluck('permission_id')
                    ->map(fn ($permissionId) => $permissionNamesById->get($permissionId))
                    ->filter()
                    ->values()
                    ->all();
            }

            $user->cohortNhsSdeCache = isset($nhsSdeApprovedUserIds[$user->id]);
        }
    }

    public function getCohortDiscoveryRolesAttribute()
    {
        if ($this->cohortRoleCache !== null) {
            return $this->cohortRoleCache;
        }

        return CohortRequest::rolesForUser($this->id);
    }

    public function getRquestRolesAttribute()
    {
        if ($this->cohortRoleCache !== null) {
            return $this->cohortRoleCache;
        }

        return CohortRequest::rolesForUser($this->id);
    }

    public function getCohortDiscoveryNhsSdeAttribute()
    {
        if ($this->cohortNhsSdeCache !== null) {
            return $this->cohortNhsSdeCache;
        }

        $cohortRequest = CohortRequest::where(['user_id' => $this->id])->first();

        return $cohortRequest !== null
            && CohortRequest::grantsAccess($cohortRequest)
            && $cohortRequest->nhse_sde_request_status === 'APPROVED'
            && $cohortRequest->nhse_sde_request_expire_at === null;
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

    public function teamUsers(): HasMany
    {
        return $this->hasMany(TeamHasUser::class, 'user_id', 'id')
            ->orderBy('team_id');
    }

    public function adminTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_has_users', 'user_id', 'team_id')
            ->whereHas('teamUserRoles', fn ($q) => $q->where(['name' => 'custodian.team.admin', 'user_id' => $this->id]));
    }

    public function cohortAdminTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_has_users', 'user_id', 'team_id')
            ->whereHas('teamUserRoles', fn ($q) => $q->where(['name' => 'custodian.team.cohortAdmin', 'user_id' => $this->id]));
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

    /** @return HasMany<CohortRequest, $this> */
    public function cohortRequests(): HasMany
    {
        return $this->hasMany(CohortRequest::class);
    }

    public function workgroups(): BelongsToMany
    {
        return $this->belongsToMany(Workgroup::class, 'user_has_workgroups')
            ->withPivot('user_id', 'workgroup_id')
            ->orderBy('user_has_workgroups.workgroup_id');
    }
}
