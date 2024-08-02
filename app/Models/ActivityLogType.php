<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLogType extends Model
{
    use HasFactory;

    protected $fillable = [
        'updated_at',
        'name',
    ];


    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'activity_log_types';

    /**
     * Indicates if this model is timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the descriptive name of this activity log type
     *
     * @var string
     */
    private $name = '';
}
