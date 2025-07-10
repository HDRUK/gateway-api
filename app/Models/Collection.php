<?php

namespace App\Models;

use Config;
use App\Http\Traits\DatasetFetch;
use App\Models\Traits\SortManager;
use App\Models\Traits\EntityCounter;
use App\Observers\CollectionObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[ObservedBy([CollectionObserver::class])]
class Collection extends Model
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
        ->whereNull('collection_has_dataset_version.deleted_at')
        ->whereIn(
            'dataset_versions.dataset_id',
            Dataset::where('status', 'ACTIVE')->select('id')
        );
    }

    public function userTools(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_tools');
    }

    public function userPublications(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_publications');
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
        ->whereNull('collection_has_dataset_version.deleted_at')
        ->whereIn(
            'dataset_versions.dataset_id',
            Dataset::where('status', 'ACTIVE')->select('id')
        );
    }

    public function applicationTools(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'collection_has_tools');
    }

    public function applicationPublications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'collection_has_publications');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'collection_has_users',
            'collection_id',
            'user_id'
        )->withPivot('role');
    }

}
