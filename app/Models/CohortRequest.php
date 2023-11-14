<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
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
}
