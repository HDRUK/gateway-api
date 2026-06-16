<?php

namespace App\Models;

use App\Http\Traits\DatasetFetch;
use App\Models\Traits\SortManager;
use App\Models\Traits\EntityCounter;
use App\Observers\PublicationObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *   schema="Publication",
 *   description="A research publication linked to one or more datasets in the Gateway",
 *   @OA\Property(property="id", type="integer", example=33),
 *   @OA\Property(property="paper_title", type="string", example="Genomic risk factors for COVID-19 severity"),
 *   @OA\Property(property="authors", type="string", nullable=true, example="Smith J, Jones A"),
 *   @OA\Property(property="year_of_publication", type="integer", nullable=true, example=2023),
 *   @OA\Property(property="paper_doi", type="string", nullable=true, example="10.1000/xyz123"),
 *   @OA\Property(property="publication_type", type="string", nullable=true),
 *   @OA\Property(property="journal_name", type="string", nullable=true, example="Nature Medicine"),
 *   @OA\Property(property="abstract", type="string", nullable=true),
 *   @OA\Property(property="url", type="string", format="uri", nullable=true),
 *   @OA\Property(property="owner_id", type="integer", nullable=true),
 *   @OA\Property(property="team_id", type="integer", nullable=true),
 *   @OA\Property(property="first_publication_date", type="string", format="date", nullable=true, example="2023-03-15"),
 *   @OA\Property(
 *     property="status",
 *     type="string",
 *     enum={"ACTIVE","DRAFT","ARCHIVED"},
 *     example="ACTIVE"
 *   ),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
#[ObservedBy([PublicationObserver::class])]
class Publication extends Model
{
    use HasFactory;
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
     * The table associated with this model.
     *
     * @var string
     */
    protected $table = 'publications';

    public $timestamps = true;

    protected $fillable = [
        'paper_title',
        'authors',
        'year_of_publication',
        'paper_doi',
        'publication_type',
        'publication_type_mk1',
        'journal_name',
        'abstract',
        'url',
        'mongo_id',
        'owner_id',
        'team_id',
        'status',
        'first_publication_date',
    ];

    protected static array $sortableColumns = [
        'updated_at',
        'paper_title',
        'year_of_publication',
        'first_publication_date',
    ];

    protected static array $countableColumns = [
        'status',
    ];

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getDatasetsViaDatasetVersion(
            new PublicationHasDatasetVersion(),
            'publication_id'
        );
    }

    /**
     * Retrieve versions associated with this publication.
     */
    public function versions()
    {
        return $this->belongsToMany(
            DatasetVersion::class,
            'publication_has_dataset_version',
            'publication_id',
            'dataset_version_id'
        )
        ->whereNull('publication_has_dataset_version.deleted_at')
        ->whereIn(
            'dataset_versions.dataset_id',
            Dataset::where('status', 'ACTIVE')->select('id')
        );
    }

    /**
     * The tools that belong to a publication.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(
            Tool::class,
            'publication_has_tools'
        )
        ->whereNull('publication_has_tools.deleted_at')
        ->where('tools.status', 'ACTIVE');
    }

    /**
     * The durs associated to a publication
     */
    public function durs(): BelongsToMany
    {
        return $this->belongsToMany(
            Dur::class,
            'dur_has_publications'
        )
        ->withPivot('dur_id', 'publication_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at')
        ->whereNull('dur_has_publications.deleted_at')
        ->where('dur.status', 'ACTIVE');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(
            Collection::class,
            'collection_has_publications',
            'publication_id',
            'collection_id'
        )
        ->whereNull('collection_has_publications.deleted_at')
        ->where('collections.status', 'ACTIVE');
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(
            Keyword::class,
            'publication_has_keywords'
        );
    }
}
