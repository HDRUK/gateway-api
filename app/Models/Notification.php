<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'notification_type',
        'message',
        'opt_in',
        'enabled',
        'email',
        'user_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
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
    private $opt_in = false;

    /**
     * Indicates whether this model is enabled or disabled
     *
     * @var bool
     */
    private $enabled = false;

    public function team(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_has_notifications');
    }

    public function user(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_has_notifications');
    }

    public function federation(): BelongsToMany
    {
        return $this->belongsToMany(Federation::class, 'federation_has_notifications');
    }
}
