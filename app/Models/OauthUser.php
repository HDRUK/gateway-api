<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OauthUser extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'user_id',
        'nonce',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'oauth_users';

    /**
     * Indicates if this model is timestamped
     *
     * @var bool
     */
    public $timestamps = true;
}
