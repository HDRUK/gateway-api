<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = [
        'uid',
        'user_id',
        'expires_at',
        'is_secondary'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
