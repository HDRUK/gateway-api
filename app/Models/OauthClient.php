<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OauthClient extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    protected $fillable = [
        'user_id',
        'redirect_url',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'oauth_clients';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Indicates if this model is timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
