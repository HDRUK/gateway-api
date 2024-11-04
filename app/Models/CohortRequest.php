<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'cohort_status',
        'request_expire_at',
        'created_at',
        'accept_declaration', // Add the new field to the fillable array
    ];

    protected $casts = [
        'cohort_status' => 'boolean',
        'accept_declaration' => 'boolean', // Add the new field to the casts array
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
