<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DatasetShareToken extends Model
{
    protected $table = 'dataset_share_tokens';

    protected $fillable = [
        'team_id',
        'dataset_id',
        'created_by',
        'token',
        'expires_at',
        'revoked_at',
        'last_submitted_at',
        'ready_for_review_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_submitted_at' => 'datetime',
        'ready_for_review_at' => 'datetime',
    ];

    protected $appends = ['status'];

    /** @return BelongsTo<Dataset, $this> */
    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return is_null($this->revoked_at) && $this->expires_at->isFuture();
    }

    /**
     * Derived, not stored: there's no "completed" column - a token is
     * "completed" once its dataset has gone live, which is what
     * revokeForActivatedDataset() also sets revoked_at from, so the two
     * states normally coincide once activation runs.
     */
    public function getStatusAttribute(): string
    {
        if ($this->dataset && $this->dataset->status === Dataset::STATUS_ACTIVE) {
            return 'completed';
        }

        if (! is_null($this->revoked_at)) {
            return 'revoked';
        }

        if ($this->expires_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * A collab share token grants an anonymous collaborator write access to
     * a draft. Once the draft goes live there's nothing left for that
     * access to protect, so close it out rather than leaving it valid until
     * it expires or an admin remembers to revoke it by hand. Called from
     * every code path that can flip a dataset to ACTIVE (DatasetService and
     * the legacy TeamDatasetController::update(), which doesn't go through
     * DatasetService at all).
     */
    public static function revokeForActivatedDataset(int $datasetId, string $status): void
    {
        if ($status !== Dataset::STATUS_ACTIVE) {
            return;
        }

        self::where('dataset_id', $datasetId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
