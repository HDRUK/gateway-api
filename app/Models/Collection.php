<?php

namespace App\Models;

use Config;
use App\Http\Traits\DatasetFetch;
use App\Models\Traits\SortManager;
use App\Models\Traits\EntityCounter;
use App\Observers\CollectionObserver;
use App\Models\Base\BaseTypesenseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @OA\Schema(
 *   schema="Collection",
 *   description="A curated collection of datasets, tools, publications and DURs",
 *   @OA\Property(property="id", type="integer", example=10),
 *   @OA\Property(property="name", type="string", example="Cardiovascular Research"),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="image_link", type="string", nullable=true, example="/collections/cardio.jpg"),
 *   @OA\Property(property="enabled", type="boolean", example=true),
 *   @OA\Property(property="keywords", type="string", nullable=true),
 *   @OA\Property(property="public", type="boolean", nullable=true, example=true),
 *   @OA\Property(property="counter", type="integer", nullable=true, example=0),
 *   @OA\Property(
 *     property="status",
 *     type="string",
 *     enum={"ACTIVE","DRAFT","ARCHIVED"},
 *     example="ACTIVE"
 *   ),
 *   @OA\Property(property="team_id", type="integer", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
#[ObservedBy([CollectionObserver::class])]
class Collection extends BaseTypesenseModel
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;
    use DatasetFetch;
    use SortManager;
    use EntityCounter;
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public string $prevStatus = '';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'collections';

    protected $fillable = [
        'name',
        'description',
        'image_link',
        'enabled',
        'keywords',
        'public',
        'counter',
        'mongo_object_id',
        'mongo_id',
        'created_at',
        'updated_at',
        'updated_on',
        'status',
        'team_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected static array $sortableColumns = [
        'name',
        'updated_at',
    ];

    protected static array $countableColumns = [
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->validateFields();
        });

        static::updating(function ($model) {
            $model->validateFields();
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
        $customPattern = "/^(" . $escapedMediaUrl . ")?\/collections\/[a-zA-Z0-9 _-]+\.(?:$allowedExtensions)$/";

        $validator = Validator::make($this->attributes, [
            'image_link' => [
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

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(
            Keyword::class,
            'collection_has_keywords'
        )
        ->whereNull('collection_has_keywords.deleted_at');
    }

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getDatasetsViaDatasetVersion(
            new CollectionHasDatasetVersion(),
            'collection_id'
        );
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(
            Tool::class,
            'collection_has_tools',
            'collection_id',
            'tool_id'
        )
        ->whereNull('collection_has_tools.deleted_at')
        ->with("user")
        ->where('tools.status', 'ACTIVE');
    }

    public function dur(): BelongsToMany
    {
        return $this->belongsToMany(
            Dur::class,
            'collection_has_durs',
            'collection_id',
            'dur_id'
        )
        ->whereNull('collection_has_durs.deleted_at')
        ->where('dur.status', 'ACTIVE');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(
            Publication::class,
            'collection_has_publications',
            'collection_id',
            'publication_id'
        )
        ->whereNull('collection_has_publications.deleted_at')
        ->where('publications.status', 'ACTIVE');
    }

    public function datasetVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            DatasetVersion::class,
            'collection_has_dataset_version',
            'collection_id',
            'dataset_version_id'
        )
        ->whereNull('collection_has_dataset_version.deleted_at')
        ->whereIn(
            'dataset_versions.dataset_id',
            Dataset::where('status', 'ACTIVE')->select('id')
        );
    }

    public function userDatasets(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            CollectionHasDatasetVersion::class,
            'collection_id', // Foreign key on the CollectionHasDatasetVersion table
            'id',            // Local key on the Collection table
            'id',            // Local key on the User table
            'user_id'        // Foreign key on the CollectionHasDatasetVersion table
        )
        ->whereNull('collection_has_dataset_version.deleted_at');
    }

    public function userTools(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_tools')
        ->whereNull('collection_has_tools.deleted_at');

    }

    public function userPublications(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_publications')
        ->whereNull('collection_has_publications.deleted_at');
    }

    public function applicationDatasets(): HasManyThrough
    {
        return $this->hasManyThrough(
            Application::class,
            CollectionHasDatasetVersion::class,
            'collection_id', // Foreign key on the CollectionHasDatasetVersion table
            'id',            // Local key on the Collection table
            'id',            // Local key on the Application table
            'application_id' // Foreign key on the CollectionHasDatasetVersion table
        )
        ->whereNull('collection_has_dataset_version.deleted_at');
    }

    public function applicationTools(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'collection_has_tools')
        ->whereNull('collection_has_tools.deleted_at');
    }

    public function applicationPublications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'collection_has_publications')
        ->whereNull('collection_has_publications.deleted_at');

    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'collection_has_users',
            'collection_id',
            'user_id'
        )->withPivot('role');
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->deleted_at === null;
    }

    /**
     * Query-level mirror of shouldBeSearchable(), for callers that need a
     * count/filter of indexable rows rather than a per-instance check (e.g.
     * AdminSearchController's eligibleCount). SoftDeletes' own global scope
     * already excludes deleted_at, so only the status check is needed here.
     */
    public function scopeIndexEligible(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * The literal name "collections" collides with a private property of the
     * same name on Typesense\Collections (the PHP SDK's collection registry
     * class) — its __get() magic method does `isset($this->{$name})` before
     * checking its own internal cache array, so a Scout collection literally
     * named "collections" causes the SDK to return its own empty private
     * array instead of a Collection instance, breaking every retrieve()/
     * create() call with "Call to a member function retrieve() on array".
     * Suffixed to sidestep the collision; unrelated to any other model here.
     */
    public function searchableAs(): string
    {
        return config('scout.prefix') . 'entity_collections';
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        // datasetVersions carries the full GWDM metadata/patch JSON blobs by
        // default; facetDatasetTitles() only reads short_title, so restrict
        // the eager load to avoid hydrating that payload for every version
        // linked to every collection in the chunk.
        return $query->with([
            'team.dataProviderColls',
            'datasetVersions:id,dataset_id,short_title',
        ]);
    }

    private function facetPublisherName(): string
    {
        return $this->team?->getAttribute('name') ?? '';
    }

    private function facetDatasetTitles(): array
    {
        return $this->datasetVersions
            ->pluck('short_title')
            ->filter(fn ($title) => is_string($title) && $title !== '')
            ->unique()->values()->all();
    }

    /**
     * Relies on 'team.dataProviderColls' being eager-loaded by
     * makeAllSearchableUsing() — a single whereIn() query for the whole
     * reindex batch, instead of a per-row DataProviderCollLoader call.
     */
    private function facetDataProviderColl(): array
    {
        return $this->team?->dataProviderColls
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->all() ?? [];
    }

    public function toSearchableArray(): array
    {
        return [
            'id'               => (string) $this->id,
            'name'             => $this->name ?? '',
            'description'      => $this->description ?? '',
            'status'           => $this->status ?? '',
            'publisherName'    => $this->facetPublisherName(),
            'datasetTitles'    => $this->facetDatasetTitles(),
            'dataProviderColl' => array_column($this->facetDataProviderColl(), 'name'),
        ];
    }

    public function typesenseSearchParameters(): array
    {
        return [
            'query_by'         => 'name,description,status',
            'query_by_weights' => '5,4,1',
        ];
    }

    public function typesenseCollectionSchema(): array
    {
        return [
            'name' => $this->searchableAs(),
            'fields' => [
                [ 'name' => 'id',               'type' => 'string', ],
                [ 'name' => 'name',             'type' => 'string', 'infix' => true ],
                [ 'name' => 'description',      'type' => 'string' ],
                [ 'name' => 'status',           'type' => 'string' ],
                [ 'name' => 'publisherName',    'type' => 'string', 'facet' => true, 'optional' => true ],
                [ 'name' => 'datasetTitles',    'type' => 'string[]', 'facet' => true, 'optional' => true ],
                [ 'name' => 'dataProviderColl', 'type' => 'string[]', 'facet' => true, 'optional' => true ],
            ],
        ];
    }
}
