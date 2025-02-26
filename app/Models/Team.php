<?php

namespace App\Models;

use Config;
use App\Observers\TeamObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[ObservedBy(TeamObserver::class)]
class Team extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

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
        'mongo_object_id',
        'notification_status',
        'is_question_bank',
        'team_logo',
        'introduction',
        'dar_modal_content',
        'service',
        'is_dar',
        'dar_modal_footer',
        'dar_modal_header',
        'pid',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'pid' => 'string',
        'enabled' => 'boolean',
        'allows_messaging' => 'boolean',
        'workflow_enabled' => 'boolean',
        'access_requests_management' => 'boolean',
        'uses_5_safes' => 'boolean',
        'is_admin' => 'boolean',
        'notification_status' => 'boolean',
        'is_question_bank' => 'boolean',
        'is_provider' => 'boolean',
        'is_dar' => 'boolean',
    ];

    protected static $htmlDecodedFields = [
        'introduction',
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
     * @var string
     */
    private $member_of = '';

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
     * Indicates whether the team uses question bank
     *
     * @var bool
     */
    private $is_question_bank = false;

    /**
     * Represents the migrated data id of a previous record to preserve internal
     * linking
     *
     * @var string
     */
    private $mongo_object_id = '';

    /**
     * Indicates whether the team is an admin
     *
     * @var bool
     */
    private $is_dar = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->validateFields();
        });

        static::updating(function ($model) {
            $model->validateFields();
        });

        static::retrieved(function ($post) {
            foreach (self::$htmlDecodedFields as $field) {
                if (isset($post->$field)) {
                    $post->$field = html_entity_decode($post->$field, ENT_QUOTES, 'UTF-8');
                }
            }
        });
    }

    /**
     * Validate fields.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateFields()
    {
        $mediaUrl = Config::get('services.media.base_url');
        $escapedMediaUrl = preg_quote($mediaUrl, '/');
        $allowedExtensions = 'jpeg|jpg|png|gif|bmp|webp';
        $customPattern = "/^(" . $escapedMediaUrl . ")?\/teams\/[a-zA-Z0-9 _-]+\.(?:$allowedExtensions)$/";

        $validator = Validator::make($this->attributes, [
            'team_logo' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($customPattern) {
                    if ($value && !filter_var($value, FILTER_VALIDATE_URL) && !preg_match($customPattern, $value)) {
                        $fail('The ' . $attribute . ' must be a valid URL or match the required format.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function getPid()
    {
        return $this->attributes['pid'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_has_users')
            ->withPivot('user_id', 'id');
    }

    public function permissions(): HasManyThrough
    {
        return $this->hasManyThrough(Permission::class, TeamHasUser::class);
    }

    public function teamUserRoles(): HasManyThrough
    {
        return $this->hasManyDeep(
            Role::class,
            [TeamHasUser::class,TeamUserHasRole::class],
            [
               'team_id', // Foreign key on the "TeamHasUser" table.
               'team_has_user_id',    // Foreign key on the "TeamUserHasRoles" table.
               'id'     // Foreign key on the "Roles" table.
            ],
            [
              'id', // Local key on the "Team" table.
              'id', // Local key on the "TeamHasUser" table.
              'role_id'  // Local key on the "TeamUserHasRole" table.
            ]
        )
            ->withIntermediate(TeamUserHasRole::class)
            ->withIntermediate(TeamHasUser::class)
            ->join("users", "team_has_users.user_id", "=", "users.id")
            ->select([
                "roles.id as role_id",
                "roles.enabled as role_enabled",
                "roles.name as role_name",
                "users.id as user_id",
                "users.name as user_name",
                "users.firstname as user_firstname",
                "users.lastname as user_lastname",
                "users.name as user_name",
                "users.email as user_email",
                "users.secondary_email as user_secondary_email",
                "users.preferred_email as user_preferred_email"
            ]);

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
