<?php

namespace App\Models;

use App\Enums\LoginMethod;
use App\Enums\SessionRevokeReason;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'login_method',
        'expires_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected $casts = [
        'login_method' => LoginMethod::class,
        'revoked_reason' => SessionRevokeReason::class,
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
