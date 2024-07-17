<?php

namespace App\Models;

use App\Models\Tool;
use App\Models\Dataset;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Publication extends Model
{
    use HasFactory, SoftDeletes, Prunable;

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

    /**
     * Get the latest datasets for this publication.
     */
    public function getLatestDatasets()
    {
        $datasetVersionIds = PublicationHasDatasetVersion::
            where('publication_id', $this->id)
            ->pluck('dataset_version_id');

        $datasetIds = DatasetVersion::whereIn('id', $datasetVersionIds)
            ->distinct()
            ->pluck('dataset_id');

        return Dataset::whereIn('id', $datasetIds)->get();
    }

    /**
     * Add an accessor for datasets to get the latest versions.
     */
    public function getDatasetsAttribute()
    {
        return $this->getLatestDatasets();
    }

    /**
     * The tools that belong to a publication.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'publication_has_tools');
    }
}
