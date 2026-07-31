<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *   schema="Notification",
 *   description="A notification preference/subscription record",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="user_id", type="integer", nullable=true, example=42),
 *   @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
 *   @OA\Property(property="message", type="string", nullable=true, example="your message here"),
 *   @OA\Property(property="opt_in", type="boolean", example=true),
 *   @OA\Property(property="enabled", type="boolean", example=true),
 *   @OA\Property(property="email", type="string", nullable=true, example="john@example.com"),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2023-04-19T12:00:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2023-04-19T12:00:00Z"),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 * )
 */
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

    public function userNotification(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
