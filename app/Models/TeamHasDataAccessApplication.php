<?php

namespace App\Models;

use App\Observers\TeamHasDataAccessApplicationObserver;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamHasDataAccessApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'dar_application_id',
        'submission_status',
        'approval_status',
        'review_id',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'team_has_dar_applications';

    protected static $observers = [
        TeamHasDataAccessApplicationObserver::class
    ];
}
