<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Federation extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'federations';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Enabled for this tag
     *
     * @var bool
     */
    private $enabled = true;

    /**
     * Tested flag to mark a successful test having been run
     *
     * @var bool
     */
    private $tested = false;

    /**
     * Feature name
     *
     * @var string
     */
    private $name = '';

    private $pid = '';

    private $auth_secret_key_location = '';

    protected $fillable = [
        'federation_type',
        'auth_type',
        'auth_secret_key_location',
        'endpoint_baseurl',
        'endpoint_datasets',
        'endpoint_dataset',
        'run_time_hour',
        'enabled',
        'tested',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'enabled' => 'boolean',
        'tested' => 'boolean',
    ];

    public function team(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_has_federations');
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'federation_has_notifications');
    }
}
