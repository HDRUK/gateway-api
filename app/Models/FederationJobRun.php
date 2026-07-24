<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class FederationJobRun extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'federation_job_runs';

    /**
     * Indicates if this model is timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'team_id',
        'federation_id',
        'pid',
        'job_uuid',
        'status',
        'details',
        'job_attempts',
    ];

    protected $casts = [
        'details' => 'array',
    ];
}
