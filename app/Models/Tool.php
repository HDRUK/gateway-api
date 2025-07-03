<?php

namespace App\Models;

use App\Http\Traits\DatasetFetch;
use App\Models\Traits\SortManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tool extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;
    use DatasetFetch;
    use SortManager;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tools';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'mongo_object_id',
        'mongo_id',
        'name',
        'url',
        'description',
        'results_insights',
        'license',
        'tech_stack',
        'category_id',
        'user_id',
        'enabled',
        'associated_authors',
        'contact_address',
        'any_dataset',
        'status',
        'team_id',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'enabled' => 'boolean',
        'any_dataset' => 'boolean',
    ];

    protected static array $sortableColumns = [
        'updated_at',
        'name',
    ];

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getDatasetsViaDatasetVersion(
            new DatasetVersionHasTool(),
            'tool_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select(['id', 'firstname', 'lastname']);
    }

    public function tag(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tool_has_tags')->whereNull('tool_has_tags.deleted_at')->withPivot('tool_id', 'tag_id', 'created_at', 'updated_at', 'deleted_at');
    }

    public function review()
    {
        return $this->hasMany(Review::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function programmingLanguages(): BelongsToMany
    {
        return $this->belongsToMany(ProgrammingLanguage::class, 'tool_has_programming_language')->whereNull('tool_has_programming_language.deleted_at');
    }

    public function programmingPackages(): BelongsToMany
    {
        return $this->belongsToMany(ProgrammingPackage::class, 'tool_has_programming_package')->whereNull('tool_has_programming_package.deleted_at');
    }

    public function typeCategory(): BelongsToMany
    {
        return $this->belongsToMany(TypeCategory::class, 'tool_has_type_category')->whereNull('tool_has_type_category.deleted_at');
        ;
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'publication_has_tools')->withPivot('deleted_at')->whereNull('publication_has_tools.deleted_at');
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license', 'id');
    }

    public function durs(): BelongsToMany
    {
        return $this->belongsToMany(Dur::class, 'dur_has_tools')->withPivot('deleted_at')->whereNull('dur_has_tools.deleted_at');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_has_tools');
    }

    /**
     * Retrieve versions associated with this tool
     */
    public function versions()
    {
        return $this->belongsToMany(DatasetVersion::class, 'dataset_version_has_tool', 'tool_id', 'dataset_version_id');
    }

    public function datasetVersions()
    {
        return $this->hasMany(DatasetVersionHasTool::class, 'tool_id');
    }
}
