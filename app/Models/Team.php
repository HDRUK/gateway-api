<?php

namespace App\Models;

use Config;
use App\Models\Base\BaseTypesenseModel;
use App\Observers\TeamObserver;
use Illuminate\Database\Eloquent\Builder;
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

/**
 * @property bool $has_published_dar_template
 *
 * @OA\Schema(
 *   schema="Team",
 *   description="A data custodian team that owns datasets and manages data access",
 *   @OA\Property(property="id", type="integer", example=7),
 *   @OA\Property(property="name", type="string", example="NHS England"),
 *   @OA\Property(property="enabled", type="boolean", example=true),
 *   @OA\Property(property="allows_messaging", type="boolean", example=true),
 *   @OA\Property(property="workflow_enabled", type="boolean", example=false),
 *   @OA\Property(property="access_requests_management", type="boolean", example=false),
 *   @OA\Property(property="uses_5_safes", type="boolean", example=false),
 *   @OA\Property(property="is_admin", type="boolean", example=false),
 *   @OA\Property(property="member_of", type="string", nullable=true),
 *   @OA\Property(property="contact_point", type="string", nullable=true, example="contact@nhsengland.nhs.uk"),
 *   @OA\Property(property="notification_status", type="boolean", nullable=true, example=true),
 *   @OA\Property(property="is_question_bank", type="boolean", example=false),
 *   @OA\Property(property="team_logo", type="string", nullable=true, example="/teams/nhse.png"),
 *   @OA\Property(property="introduction", type="string", nullable=true),
 *   @OA\Property(property="dar_modal_content", type="string", nullable=true),
 *   @OA\Property(property="dar_modal_header", type="string", nullable=true),
 *   @OA\Property(property="dar_modal_footer", type="string", nullable=true),
 *   @OA\Property(property="service", type="string", nullable=true),
 *   @OA\Property(property="is_dar", type="boolean", example=false),
 *   @OA\Property(property="pid", type="string", nullable=true, description="Public identifier, cast to string"),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
#[ObservedBy(TeamObserver::class)]
class Team extends BaseTypesenseModel
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
        'dar_modal_content',
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
        $customPattern = "/^(" . $escapedMediaUrl . ")?\/teams\/[a-zA-Z0-9 _\-()]+\.(?:$allowedExtensions)$/";

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

    /** @return \Staudenmeir\EloquentHasManyDeep\HasManyDeep<Role, $this> */
    public function teamUserRoles(): \Staudenmeir\EloquentHasManyDeep\HasManyDeep
    {
        return $this->hasManyDeep(
            Role::class,
            [TeamHasUser::class, TeamUserHasRole::class],
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

    public function teamUsers(): HasMany
    {
        return $this->hasMany(TeamHasUser::class, 'team_id', 'id');
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

    public function aliases(): BelongsToMany
    {
        return $this->belongsToMany(Alias::class, 'team_has_aliases');
    }

    public function datasets(): HasMany
    {
        return $this->hasMany(Dataset::class);
    }

    /** @return BelongsToMany<DataProviderColl, $this> */
    public function dataProviderColls(): BelongsToMany
    {
        return $this->belongsToMany(
            DataProviderColl::class,
            'data_provider_coll_has_teams'
        );
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->getAttribute('enabled') && $this->deleted_at === null;
    }

    /**
     * Query-level mirror of shouldBeSearchable(), for callers that need a
     * count/filter of indexable rows rather than a per-instance check (e.g.
     * AdminSearchController's eligibleCount). SoftDeletes' own global scope
     * already excludes deleted_at, so only the enabled check is needed here.
     */
    public function scopeIndexEligible(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with([
            'aliases',
            'datasets' => fn ($q) => $q->where('status', Dataset::STATUS_ACTIVE)
                ->with(['latestMetadata' => fn ($q2) => $q2->select([
                    'dataset_versions.id',
                    'dataset_versions.dataset_id',
                    'dataset_versions.short_title',
                    'dataset_versions.metadata',
                ])->with('spatialCoverage')]),
        ]);
    }

    /**
     * Latest, non-null version of every ACTIVE dataset owned by this team.
     * Mirrors legacy reindexElasticDataProvider(), bounded to this team's own
     * datasets rather than an entire network's, so the per-record metadata
     * load stays small (see DataCustodianNetworkHydrator for the OOM this
     * pattern avoids at larger scale).
     */
    private function activeDatasetVersions()
    {
        return $this->datasets
            ->where('status', Dataset::STATUS_ACTIVE)
            ->map(fn ($dataset) => $dataset->getAttribute('latestMetadata'))
            ->filter();
    }

    private function facetDatasetTitles(): array
    {
        return $this->activeDatasetVersions()
            ->pluck('short_title')
            ->filter(fn ($title) => is_string($title) && $title !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function facetDataTypes(): array
    {
        return $this->activeDatasetVersions()
            ->flatMap(fn ($version) => explode(';,;', data_get($version->metadata, 'metadata.summary.datasetType', '') ?? ''))
            ->filter(fn ($type) => $type !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function facetGeographicLocations(): array
    {
        return $this->activeDatasetVersions()
            ->flatMap(fn ($version) => $version->spatialCoverage->pluck('region'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function activeDatasetVersionIds(): array
    {
        return $this->activeDatasetVersions()->pluck('id')->all();
    }

    private function durTitlesText(): string
    {
        $versionIds = $this->activeDatasetVersionIds();

        $linkedTitles = empty($versionIds) ? collect() : Dur::whereIn(
            'id',
            DurHasDatasetVersion::whereIn('dataset_version_id', $versionIds)->pluck('dur_id')
        )->pluck('project_title');

        $ownTitles = Dur::where('team_id', $this->id)->pluck('project_title');

        return $linkedTitles->merge($ownTitles)->filter()->unique()->implode(',');
    }

    private function toolNamesText(): string
    {
        $versionIds = $this->activeDatasetVersionIds();
        if (empty($versionIds)) {
            return '';
        }

        return Tool::whereIn(
            'id',
            DatasetVersionHasTool::whereIn('dataset_version_id', $versionIds)->pluck('tool_id')
        )->pluck('name')->filter()->unique()->implode(',');
    }

    private function publicationTitlesText(): string
    {
        $versionIds = $this->activeDatasetVersionIds();
        if (empty($versionIds)) {
            return '';
        }

        return Publication::whereIn(
            'id',
            PublicationHasDatasetVersion::whereIn('dataset_version_id', $versionIds)
                ->whereNull('deleted_at')
                ->pluck('publication_id')
        )->pluck('paper_title')->filter()->unique()->implode(',');
    }

    private function collectionNamesText(): string
    {
        $versionIds = $this->activeDatasetVersionIds();
        if (empty($versionIds)) {
            return '';
        }

        return Collection::whereIn(
            'id',
            CollectionHasDatasetVersion::whereIn('dataset_version_id', $versionIds)
                ->whereNull('deleted_at')
                ->pluck('collection_id')
        )->where('status', Collection::STATUS_ACTIVE)->pluck('name')->filter()->unique()->implode(',');
    }

    private function teamAliases(): array
    {
        return $this->aliases->pluck('name')->filter()->values()->all();
    }

    public function toSearchableArray(): array
    {
        return [
            'id'                 => (string) $this->id,
            'name'               => $this->getAttribute('name') ?? '',
            'datasetTitles'      => $this->facetDatasetTitles(),
            'dataType'           => $this->facetDataTypes(),
            'geographicLocation' => $this->facetGeographicLocations(),
            'durTitles'          => $this->durTitlesText(),
            'toolNames'          => $this->toolNamesText(),
            'publicationTitles'  => $this->publicationTitlesText(),
            'collectionNames'    => $this->collectionNamesText(),
            'teamAliases'        => $this->teamAliases(),
        ];
    }

    public function typesenseSearchParameters(): array
    {
        return [
            'query_by'         => 'name,datasetTitles,durTitles,toolNames,publicationTitles,collectionNames,teamAliases',
            'query_by_weights' => '10,3,2,2,2,2,3',
        ];
    }

    public function typesenseCollectionSchema(): array
    {
        return [
            'name' => $this->searchableAs(),
            'fields' => [
                [ 'name' => 'id',                   'type' => 'string' ],
                [ 'name' => 'name',                 'type' => 'string', 'infix' => true ],
                [ 'name' => 'datasetTitles',        'type' => 'string[]', 'facet' => true, 'optional' => true ],
                [ 'name' => 'dataType',             'type' => 'string[]', 'facet' => true, 'optional' => true ],
                [ 'name' => 'geographicLocation',   'type' => 'string[]', 'facet' => true, 'optional' => true ],
                [ 'name' => 'durTitles',            'type' => 'string', 'optional' => true ],
                [ 'name' => 'toolNames',            'type' => 'string', 'optional' => true ],
                [ 'name' => 'publicationTitles',    'type' => 'string', 'optional' => true ],
                [ 'name' => 'collectionNames',      'type' => 'string', 'optional' => true ],
                [ 'name' => 'teamAliases',          'type' => 'string[]', 'optional' => true ],
            ],
        ];
    }
}
