<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataAccessApplicationStatus extends Model
{
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    /**
     * The table associated with this model
     *
     * @var string
     */
    protected $table = 'dar_application_statuses';

    protected $appends = [
        'days_between_states'
    ];

    public function getDaysBetweenStatesAttribute(): int
    {
        $prev = static::where('application_id', $this->application_id)
            ->where('created_at', '<', $this->created_at)
            ->orderByDesc('created_at')
            ->first();

        if (!$prev) {
            return 0;
        }

        return (int) $prev->created_at->diffInDays($this->created_at);
    }

    protected $fillable = [
        'application_id',
        'approval_status',
        'submission_status',
        'review_id',
        'team_id',
    ];



}
