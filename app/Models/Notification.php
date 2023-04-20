<?php

namespace App\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'updated_at',
        'deleted_at',
        'notification_type',
        'message',
        'opt_in',
        'enabled',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'notifications';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the notification type
     * 
     * @var string
     */
    private $notification_type = '';

    /**
     * Indicates the message associated with this notification
     * 
     * @var string
     */
    private $message = '';

    /**
     * Indicates whether entities opt in to receiving this notification
     * 
     * @var bool
     */
    private $opt_in = '';

    /**
     * Indicates whether this model is enabled or disabled
     * 
     * @var bool
     */
    private $enabled = false;

    /**
     * The teams that belong to the notification.
     */
    public function team(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_has_notifications');
    }
}
