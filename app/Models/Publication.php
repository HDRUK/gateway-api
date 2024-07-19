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
     * Get the latest datasets
     */
    public function getLatestDatasets()
    {
        // Step 1: Retrieve all version IDs associated with this instance
        $versionIds = $this->versions()->pluck('dataset_version_id')->toArray();

        // Step 2: Use the version IDs to find all related dataset IDs through the linkage table
        $datasetIds = DatasetVersion::whereIn('id', $versionIds)
            ->pluck('dataset_id')
            ->unique()
            ->toArray();

        // Step 3: Retrieve all datasets using the collected dataset IDs
        $datasets = Dataset::whereIn('id', $datasetIds)->get();

        // Initialize an array to store transformed datasets
        $transformedDatasets = [];

        // Iterate through each dataset and add associated dataset versions
        foreach ($datasets as $dataset) {
            // Retrieve dataset version IDs associated with the current dataset
            $latestVersionId = $dataset->latestVersion()->id;
            
            if(in_array($latestVersionId, $versionIds)) {
                $dataset->dataset_version_ids = [$latestVersionId];
            } else {
                $dataset->dataset_version_ids = [];
            }
            // Add the enhanced dataset to the transformed datasets array
            $transformedDatasets[] = $dataset;
        }

        // Return the array of transformed datasets
        return $transformedDatasets;
    }

    /**
     * Add an accessor for datasets to get the latest versions.
     */
    public function getLatestDatasetsAttribute()
    {
        return $this->getLatestDatasets();
    }

    /**
     * Get all datasets associated with the latest versions.
     */
    public function getAllDatasets()
    {
        // Step 1: Retrieve all version IDs associated with this instance
        $versionIds = $this->versions()->pluck('dataset_version_id')->toArray();

        // Step 2: Use the version IDs to find all related dataset IDs through the linkage table
        $datasetIds = DatasetVersion::whereIn('id', $versionIds)
            ->pluck('dataset_id')
            ->unique()
            ->toArray();

        // Step 3: Retrieve all datasets using the collected dataset IDs
        $datasets = Dataset::whereIn('id', $datasetIds)->get();

        // Initialize an array to store transformed datasets
        $transformedDatasets = [];

        // Iterate through each dataset and add associated dataset versions
        foreach ($datasets as $dataset) {
            // Retrieve dataset version IDs associated with the current dataset
            $datasetVersionIds = $dataset->versions()->whereIn('id', $versionIds)->pluck('id')->toArray();

            // Add associated dataset versions to the dataset object
            $dataset->dataset_version_ids = $datasetVersionIds;
            // Add the enhanced dataset to the transformed datasets array
            $transformedDatasets[] = $dataset;
        }

        // Return the array of transformed datasets
        return $transformedDatasets;
    }

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getAllDatasets();
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
