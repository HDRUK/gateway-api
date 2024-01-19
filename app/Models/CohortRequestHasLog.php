<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CohortRequestHasLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cohort_request_id', 'cohort_request_log_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'cohort_request_has_logs';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;

}
