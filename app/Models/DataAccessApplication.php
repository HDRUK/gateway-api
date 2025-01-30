<?php

namespace App\Models;

use App\Observers\DataAccessApplicationObserver;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ObservedBy([DataAccessApplicationObserver::class])]
class DataAccessApplication extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_applications';

    protected static $observers = [
        DataAccessApplicationObserver::class
    ];

    protected $fillable = [
        'applicant_id',
        'submission_status',
        'approval_status',
        'project_title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(DataAccessApplicationHasQuestion::class, 'application_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(DataAccessApplicationAnswer::class, 'application_id');
    }
}
