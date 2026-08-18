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

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return is_null($this->revoked_at) && $this->expires_at->isFuture();
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }
}
