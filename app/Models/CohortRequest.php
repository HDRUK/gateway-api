<?php

namespace App\Models;

use App\Enums\CohortRequestEligibility;
use App\Enums\CohortRequestStatus;
use Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property-read bool $has_access
 */
class CohortRequest extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cohort_requests';

    protected $fillable = [
        'user_id',
        'request_status',
        'request_expire_at',
        'created_at',
        'accept_declaration',
        'access_to_env',
        'nhse_sde_request_status',
        'nhse_sde_requested_at',
        'nhse_sde_self_declared_approved_at',
        'nhse_sde_request_expire_at',
        'nhse_sde_updated_at',
    ];

    protected $casts = [
        'request_status' => CohortRequestStatus::class,
        'accept_declaration' => 'boolean',
        'request_expire_at' => 'datetime',
        'nhse_sde_requested_at' => 'datetime',
        'nhse_sde_self_declared_approved_at' => 'datetime',
        'nhse_sde_request_expire_at' => 'datetime',
        'nhse_sde_updated_at' => 'datetime',
    ];

    protected $appends = [
        'has_access',
    ];

    public const REQUEST_APPROVED = 'APPROVED';
    public const REQUEST_IN_PROCESS = 'IN PROCESS';
    public const REQUEST_APPROVAL_REQUESTED = 'APPROVAL REQUESTED';
    public const REQUEST_PENDING = 'PENDING';
    public const REQUEST_EXPIRED = 'EXPIRED';

    /**
     * Statuses under which a user currently has CDS access.
     */
    public const ACCESS_GRANTING_STATUSES = [
        CohortRequestStatus::APPROVED,
        CohortRequestStatus::RENEWING,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateTrueExpiry(string $expiryField = 'request_expire_at'): ?Carbon
    {
        /** @var Carbon|null $explicitExpiry */
        $explicitExpiry = $this->$expiryField;

        if ($expiryField === 'nhse_sde_request_expire_at') {
            $basedOnUpdatedAt = $this->nhse_sde_updated_at
                ? $this->nhse_sde_updated_at->copy()->addDays((int) Config::get('cohort.cohort_nhse_sde_access_expiry_time_in_days'))
                : null;
        } else {
            $basedOnUpdatedAt = $this->updated_at->copy()->addDays((int) Config::get('cohort.cohort_access_expiry_time_in_days'));
        }

        $candidates = array_filter([$basedOnUpdatedAt, $explicitExpiry]);

        if (empty($candidates)) {
            return null;
        }

        return Carbon::instance(min($candidates));
    }

    public static function grantsAccess(CohortRequest $request): bool
    {
        if (! in_array($request->request_status, self::ACCESS_GRANTING_STATUSES, true)) {
            return false;
        }

        $trueExpiry = $request->calculateTrueExpiry('request_expire_at');

        return $trueExpiry === null || Carbon::now()->lessThan($trueExpiry);
    }

    public function eligibility(): CohortRequestEligibility
    {
        return match (true) {
            $this->request_status === CohortRequestStatus::RENEWING => CohortRequestEligibility::ALREADY_RENEWING,
            $this->request_status === CohortRequestStatus::APPROVED => CohortRequestEligibility::RENEW,
            in_array($this->request_status, [CohortRequestStatus::REJECTED, CohortRequestStatus::EXPIRED], true) => CohortRequestEligibility::REAPPLY,
            default => CohortRequestEligibility::BLOCKED,
        };
    }

    /**
     * The cohort permission names granted to this user, or [] if they don't
     * currently have access. Shared by every place that needs a user's
     * cohort-derived roles (JWT claims, SSO claims, CRM sync).
     */
    public static function rolesForUser(?int $userId): array
    {
        if ($userId === null) {
            return [];
        }

        $cohortRequest = self::where(['user_id' => $userId])->first();

        if (! $cohortRequest || ! self::grantsAccess($cohortRequest)) {
            return [];
        }

        $permissionIds = CohortRequestHasPermission::where([
            'cohort_request_id' => $cohortRequest->id,
        ])->pluck('permission_id');

        return Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
    }

    public function getHasAccessAttribute(): bool
    {
        return self::grantsAccess($this);
    }

    /**
     * The logs that belong to the cohort request.
     */
    public function logs(): BelongsToMany
    {
        return $this->belongsToMany(CohortRequestLog::class, 'cohort_request_has_logs');
    }

    /**
     * The permissions that belong to the cohort request.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'cohort_request_has_permissions');
    }

    /**
     * Scope a query to only include cohort requests that have users with email with a specific value.
     *
     * @param Builder $query
     * @param string $value
     * @return Builder
     */
    public function scopeFilterByEmail(Builder $query, string $value): Builder
    {
        return $query->whereHas('user', function ($query) use ($value) {
            $query->where('email', 'LIKE', '%' . $value . '%');
        });
    }

    /**
     * Scope a query to only include cohort requests that have users with organisation with a specific value.
     *
     * @param Builder $query
     * @param string $value
     * @return Builder
     */
    public function scopeFilterByOrganisation(Builder $query, string $value): Builder
    {
        return $query->whereHas('user', function ($query) use ($value) {
            $query->where('organisation', 'LIKE', '%' . $value . '%');
        });
    }

    /**
     * Scope a query to only include cohort requests that have users with organisation or name with a specific value.
     *
     * @param Builder $query
     * @param string $value
     * @return Builder
     */
    public function scopeFilterByOrganisationOrName(Builder $query, string $value): Builder
    {
        return $query->whereHas('user', function ($query) use ($value) {
            $query->where('organisation', 'LIKE', '%' . $value . '%')
                  ->orWhere('name', 'LIKE', '%' . $value . '%');
        });
    }

    public function scopeFilterByMultiOrganisation(Builder $query, array $values): Builder
    {
        if (empty($values)) {
            return $query;
        }
        return $query->whereHas('user', function ($query) use ($values) {
            $query->where(function ($query) use ($values) {
                foreach ($values as $value) {
                    $query->orWhere('organisation', 'LIKE', '%' . $value . '%');
                }
            });
        });
    }

    public function scopeFilterByMultiOrganisationExact(Builder $query, array $values): Builder
    {
        if (empty($values)) {
            return $query;
        }
        return $query->whereHas('user', function ($query) use ($values) {
            $query->whereIn('organisation', $values);
        });
    }

    public function scopeFilterByMultiRequestStatus(Builder $query, array $values): Builder
    {
        if (empty($values)) {
            return $query;
        }
        return $query->whereHas('user', function ($query) use ($values) {
            if (in_array("NULL", $values)) {
                $query->whereIn('request_status', $values)->orWhere('request_status', null);
            } else {
                $query->whereIn('request_status', $values);
            }
        });
    }

    public function scopeFilterByMultiNhseSdeRequestStatus(Builder $query, array $values): Builder
    {
        if (empty($values)) {
            return $query;
        }
        return $query->whereHas('user', function ($query) use ($values) {
            if (in_array("NULL", $values)) {
                $query->whereIn('nhse_sde_request_status', $values)->orWhere('nhse_sde_request_status', null);
            } else {
                $query->whereIn('nhse_sde_request_status', $values);
            }
        });
    }

    public function scopeFilterBetween(Builder $query, string $fromDate, string $toDate): Builder
    {
        return $query->whereBetween('cohort_requests.created_at', [$fromDate, $toDate]);
    }

    /**
     * Scope a query to only include cohort requests that have users with name with a specific value.
     *
     * @param Builder $query
     * @param string $value
     * @return Builder
     */
    public function scopeFilterByUserName(Builder $query, string $value): Builder
    {
        return $query->whereHas('user', function ($query) use ($value) {
            $query->where('name', 'LIKE', '%' . $value . '%');
        });
    }
}
