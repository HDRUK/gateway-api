<?php

namespace App\Models;

use Hash;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Team extends Model
{
    use HasFactory;

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_has_users')
            ->withPivot('user_id', 'id');
    }

    public function permissions(): HasManyThrough
    {
        return $this->hasManyThrough(Permission::class, TeamHasUser::class);
    }

}
