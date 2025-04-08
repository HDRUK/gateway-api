<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAccessApplicationComment extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_application_comments';

    protected $fillable = [
        'review_id',
        'user_id',
        'team_id',
        'comment',
    ];

    protected $appends = ['user_name', 'team_name'];

    protected $hidden = ['user', 'team'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserNameAttribute(): ?string
    {
        return $this->user ? $this->user->name : null;
    }
    public function getTeamNameAttribute(): ?string
    {
        return $this->team ? $this->team->name : null;
    }
}
