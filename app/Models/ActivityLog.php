<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

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


    public function scopeGetAll($query, $jwtUser)
    {
        if (!count($jwtUser)) {
            return $query;
        }

        $userId = $jwtUser['id'];
        $userIsAdmin = (bool) $jwtUser['is_admin'];

        if (!$userIsAdmin) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }

}
