<?php

namespace App\Models;

use App\Http\Traits\WithJwtUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;
    use WithJwtUser;

    protected $fillable = [
        'updated_at',
        'event_type',
        'user_type_id',
        'log_type_id',
        'user_id',
        'version',
        'html',
        'plain_text',
        'user_id_mongo',
        'version_id_mongo',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'activity_logs';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the event type associated with this record
     * 
     * @var string
     */
    private $event_type = '';

    /**
     * Represents the user type id associated with this record
     * 
     * @var int
     */
    private $user_type_id = 0;

    /**
     * Represents the log type id associated with this record
     * 
     * @var int
     */
    private $log_type_id = 0;

    /**
     * Represents the user id tied to this record
     * 
     * @var int
     */
    private $user_id = 0;

    /**
     * Represents a version string associated with this record
     * 
     * @var string
     */
    private $version = '';

    /**
     * Represents the html formatted message associated with this record
     * 
     * @var string
     */
    private $html = '';

    /**
     * Represents the plain text message associated with this record
     * 
     * @var string
     */
    private $plain_text = '';

    /**
     * Represents the mongo id associated with this record if
     * the record is historical
     * 
     * @var string
     */
    private $user_id_mongo = '';

    /**
     * Represents the mongo id associated with this record if
     * the record is historical
     * 
     * @var string
     */
    private $version_id_mongo = '';
}
