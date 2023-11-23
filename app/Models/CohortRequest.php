<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CohortRequest extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'cohort_requests';

    protected $fillable = [
        'user_id',
        'request_status',
        'cohort_status',
        'request_expire_at',
        'created_at',
    ];

    protected $casts = [
        'cohort_status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByEmail($query, $value): Builder
    {
        return $query->whereHas('user', function ($query) use ($value) {
            $query->where('email', 'LIKE', '%' . $value . '%');
        });
    }

    /**
     * Scope a query to only include cohort requests that have users with organisation with a specific value.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByOrganisation($query, $value): Builder
    {
        return $query->whereHas('user', function ($query) use ($value) {
            $query->where('organisation', 'LIKE', '%' . $value . '%');
        });
    }

    /**
     * Scope a query to only include cohort requests that have users with name with a specific value.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByUserName($query, $value): Builder
    {
        return $query->whereHas('user', function ($query) use ($value) {
            $query->where('name', 'LIKE', '%' . $value . '%');
        });
    }
}
