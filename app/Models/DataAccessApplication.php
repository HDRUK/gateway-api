<?php

namespace App\Models;

use App\Models\Traits\SortManager;
use App\Observers\DataAccessApplicationObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataAccessApplication extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use SortManager;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_applications';

    protected $fillable = [
        'applicant_id',
        'project_title',
        'project_id',
        'application_type',
        'submission_status',
        'approval_status',
        'is_joint',
        'status_review_id',
    ];

    protected $casts = [
        'is_joint' => 'boolean',
    ];

    protected $appends = ['teams'];

    protected static array $sortableColumns = [
        'project_title',
        'updated_at',
    ];

    protected static $observers = [
        DataAccessApplicationObserver::class
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

    public function datasets(): HasMany
    {
        return $this->hasMany(DataAccessApplicationHasDataset::class, 'dar_application_id');
    }

    // TODO: reinstate this teams method and remove the custom attribute when the
    // web has been updated to get submission_status and approval_status from the
    // application rather than from the team relation.

    // public function teams(): HasMany
    // {
    //     return $this->hasMany(TeamHasDataAccessApplication::class, 'dar_application_id');
    // }

    protected function teams(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hasMany(TeamHasDataAccessApplication::class, 'dar_application_id')
                ->get()->transform(function ($team) {
                    $team['submission_status'] = $this->submission_status;
                    $team['approval_status'] = $this->approval_status;
                    return $team;
                })
        );
    }

}
