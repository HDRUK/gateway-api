<?php

namespace App\Models;

use App\Models\Tool;
use App\Models\PublicationHasDatasetVersion;
use App\Http\Traits\GetDatasetViaDatasetVersions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property array $dataset_version_ids
 */
class Publication extends Model
{
    use HasFactory, SoftDeletes, Prunable, GetDatasetViaDatasetVersions;

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
    ];

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getDatasetsViaDatasetVersion(
            PublicationHasDatasetVersion::class,
            'publication_id'
        );
    }

    /**
     * Retrieve versions associated with this publication.
     */
    public function versions()
    {
        return $this->belongsToMany(DatasetVersion::class, 'publication_has_dataset_version', 'publication_id', 'dataset_version_id');
    }

    /**
     * The tools that belong to a publication.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'publication_has_tools');
    }
}
