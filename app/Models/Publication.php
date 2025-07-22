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
    ];

    protected static array $sortableColumns = [
        'updated_at',
        'paper_title',
        'year_of_publication',
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
}
