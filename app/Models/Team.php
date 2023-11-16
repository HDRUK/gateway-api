<?php

namespace App\Models;

use App\Models\User;
use App\Models\Permission;
use App\Models\Application;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Team extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    protected $fillable = [
        'updated_at',
        'deleted_at',
        'name',
        'enabled',
        'allows_messaging',
        'workflow_enabled',
        'access_requests_management',
        'uses_5_safes',
        'is_admin',
        'member_of',
        'contact_point',
        'application_form_updated_by',
        'application_form_updated_on',
        'mdm_folder_id',
        'mongo_object_id',
        'notification_status',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'enabled' => 'boolean',
        'allows_messaging' => 'boolean',
        'workflow_enabled' => 'boolean',
        'access_requests_management' => 'boolean',
        'uses_5_safes' => 'boolean',
        'is_admin' => 'boolean',
        'notification_status' => 'boolean',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'teams';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Indicates the name of the team
     * 
     * @var string
     */
    private $name = '';

    /**
     * Indicates whether this model is enabled or disabled
     * 
     * @var bool
     */
    private $enabled = false;

    /**
     * Indicates whether the team allows messaging or not
     * 
     * @var bool
     */
    private $allows_messaging = false;

    /**
     * Indicates whether the team has workflows enabled
     * 
     * @var bool
     */
    private $workflow_enabled = false;

    /**
     * Indicates whether the team has acces requst management enabled
     * 
     * @var bool
     */
    private $access_requests_management = false;

    /**
     * Indicates whether the team uses 5 safes
     * 
     * @var bool
     */
    private $uses_5_safes = false;

    /**
     * Indicates whether the team is an admin
     * 
     * @var bool
     */
    private $is_admin = false;

    /**
     * Indicates the organisation the team is a member of
     * 
     * @var int
     */
    private $member_of = 0;

    /**
     * Represents the contact point for the team
     * 
     * @var string
     */
    private $contact_point = '';

    /**
     * Represents the person to last update the application
     * 
     * @var string
     */
    private $application_form_updated_by = '';

    /**
     * Indicates the datetime when the application was last updated
     * 
     * @var string
     */
    private $application_form_updated_on = '';

    /**
     * Represents the migrated data id of a previous record to preserve internal
     * linking
     * 
     * @var string
     */
    private $mongo_object_id = '';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_has_users')
            ->withPivot('user_id', 'id');
    }

    public function permissions(): HasManyThrough
    {
        return $this->hasManyThrough(Permission::class, TeamHasUser::class);
    }

    /**
     * The notifications that belong to the team.
     */
    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'team_has_notifications');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function federation(): BelongsToMany
    {
        return $this->belongsToMany(Federation::class, 'team_has_federations');
    }
}
