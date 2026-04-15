<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

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

        $holidays = BankHoliday::query()
            ->where([
                'country' => 'GB',
                'region' => 'england-and-wales',
            ])
            ->whereBetween('holiday_date', [
                $prev->created_at->toDateString(),
                $this->created_at->toDateString(),
            ])
            ->pluck('holiday_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $days = 0;
        $current = $prev->created_at->copy()->addDay();

        while ($current->lte($this->created_at)) {
            if (!$current->isWeekend() && !in_array($current->toDateString(), $holidays)) {
                $days++;
            }

            $current->addDay();
        }

        return $days;
    }

    protected $fillable = [
        'application_id',
        'approval_status',
        'submission_status',
        'review_id',
        'team_id',
    ];



}
