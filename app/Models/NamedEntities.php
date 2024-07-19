<?php

namespace App\Models;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property array $dataset_version_ids
 */
class NamedEntities extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'named_entities';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'name'
    ];

    /**
     * Name of this named_entity
     * 
     * @var string
     */
    private $name = '';

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
     * Retrieve versions associated with this named entity.
     */
    public function versions()
    {
        return $this->belongsToMany(DatasetVersion::class, 'dataset_version_has_named_entities', 'named_entities_id', 'dataset_version_id');
    }

    
}
