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

    protected $fillable = [
        'application_id',
        'approval_status',
        'submission_status',
    ];

}
