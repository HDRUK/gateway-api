<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FederationHasNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'federation_id',
        'notification_id',
    ];

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'federation_has_notifications';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
