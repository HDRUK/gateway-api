<?php

namespace App\Models;

use App\Models\Dur;
use App\Models\Tool;
use App\Models\Collection;
use App\Models\Application;
use App\Models\DataVersion;
use App\Models\NamedEntities;
use App\Models\Publication;
use App\Models\SpatialCoverage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property array $dataset_version_ids
 */
class Dataset extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const ORIGIN_MANUAL = 'MANUAL';
    public const ORIGIN_API = 'API';
    public const ORIGIN_FMA = 'FMA';

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'datasets';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'team_id',
        'mongo_object_id',
        'mongo_id',
        'mongo_pid',
        'datasetid',
        'metadata',
        'created',
        'updated',
        'submitted',
        'pid',
        'version',
        'create_origin',
        'status',
        'is_cohort_discovery',
    ];

    protected $casts = [
        'is_cohort_discovery' => 'boolean',
    ];

    
    /**
     * The version history of metadata that respond to this dataset.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DatasetVersion::class, 'dataset_id');
    }

    /**
     * The very latest version of a DatasetVersion object that corresponds to this dataset.
     **/
    public function latestVersion(): DatasetVersion
    {
        $version = DatasetVersion::where('dataset_id', $this->id)
            ->select(['version','id'])
            ->latest('version')
            ->first()
            ->id;
        return DatasetVersion::findOrFail($version);
    }


    /**
     * The very latest metadata via a hasOne relation
     */
    public function latestMetadata(): HasOne
    {
        return $this->hasOne(DatasetVersion::class, 'dataset_id')->withTrashed()->latest('version');
    }

    /**
     * The very latest version number that corresponds to this dataset.
     */
    public function lastMetadataVersionNumber(): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->select('version')->first();
    }

    /**
     * The very last metadata as an array
     */
    public function lastMetadata(): array
    {
        $datasetVersion = DatasetVersion::where('dataset_id', $this->id)
            ->latest('version')->select('metadata')->first();
        return  $datasetVersion['metadata'];
    }

     //NAMED ENTITIES 

    /**
     * Get the latest version's named entities.
     */
    public function getLatestNamedEntities()
    {
        $versionId = $this->latestVersion()->id;
        $linkage = DatasetVersionHasNamedEntities::where('dataset_version_id', $versionId);
        $namedEntityIds =  $linkage->pluck('named_entities_id')->unique()->toArray();
        $namedEntities = NamedEntities::whereIn('id', $namedEntityIds)->get();

        // Initialize an array to store transformed named entities
        $transformedNamedEntities = [];

        // Iterate through each named entity and add associated dataset versions
        foreach ($namedEntities as $namedEntity) {
            // Retrieve dataset version IDs associated with the current named entity
            $datasetVersionIds = DatasetVersionHasNamedEntities::where('named_entities_id', $namedEntity->id)
                ->where('dataset_version_id', $versionId)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the named entity object
            $namedEntity->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced named entity to the transformed named entities array
            $transformedNamedEntities[] = $namedEntity;
        }

        // Return the array of transformed named entities
        return $transformedNamedEntities;
    }

    // Add an accessor for the latest named entities
    public function getLatestNamedEntitiesAttribute()
    {
        return $this->getLatestNamedEntities();
    }

    /**
     * Get all named entities associated with the latest version.
     */
    public function getAllNamedEntities()
    {
        $versionIds = $this->versions()->pluck('id')->toArray();
        $linkage = DatasetVersionHasNamedEntities::whereIn('dataset_version_id', $versionIds);
        $namedEntityIds =  $linkage->pluck('named_entities_id')->unique()->toArray();
        $namedEntities = NamedEntities::whereIn('id', $namedEntityIds)->get();

        // Initialize an array to store transformed named entities
        $transformedNamedEntities = [];

        // Iterate through each named entity and add associated dataset versions
        foreach ($namedEntities as $namedEntity) {
            // Retrieve dataset version IDs associated with the current named entity
            $datasetVersionIds = DatasetVersionHasNamedEntities::where('named_entities_id', $namedEntity->id)
                ->whereIn('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the named entity object
            $namedEntity->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced named entity to the transformed named entities array
            $transformedNamedEntities[] = $namedEntity;
        }

        // Return the array of transformed named entities
        return $transformedNamedEntities;
    }

    // Accessor for all named entities
    public function getAllNamedEntitiesAttribute()
    {
        return $this->getAllNamedEntities();
    }


    //SPATIAL COVERAGE 

    /**
     * Get the latest version's spatial coverages.
     */
    public function getLatestSpatialCoverages()
    {
        $versionIds = $this->latestVersion()->id;
        $linkage = DatasetVersionHasSpatialCoverage::where('dataset_version_id', $versionIds);
        $spatialCoverageIds =  $linkage->pluck('spatial_coverage_id')->unique()->toArray();
        $spatialCoverages = SpatialCoverage::whereIn('id', $spatialCoverageIds)->get();

        // Initialize an array to store transformed spatial coverages
        $transformedSpatialCoverages = [];

        // Iterate through each spatial coverage and add associated dataset versions
        foreach ($spatialCoverages as $spatialCoverage) {
            // Retrieve dataset version IDs associated with the current spatial coverage
            $datasetVersionIds = DatasetVersionHasSpatialCoverage::where('spatial_coverage_id', $spatialCoverage->id)
                ->where('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the spatial coverage object
            $spatialCoverage->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced spatial coverage to the transformed spatial coverages array
            $transformedSpatialCoverages[] = $spatialCoverage;
        }

        // Return the array of transformed spatial coverages
        return $transformedSpatialCoverages;
    }

    // Add an accessor for the latest spatial coverages
    public function getLatestSpatialCoveragesAttribute()
    {
        return $this->getLatestSpatialCoverages();
    }

    /**
     * Get all spatial coverages associated with the latest version.
     */
    public function getAllSpatialCoverages()
    {
        $versionIds = $this->versions()->pluck('id')->toArray();
        $linkage = DatasetVersionHasSpatialCoverage::whereIn('dataset_version_id', $versionIds);
        $spatialCoverageIds =  $linkage->pluck('spatial_coverage_id')->unique()->toArray();
        $spatialCoverages = SpatialCoverage::whereIn('id', $spatialCoverageIds)->get();

        // Initialize an array to store transformed spatial coverages
        $transformedSpatialCoverages = [];

        // Iterate through each spatial coverage and add associated dataset versions
        foreach ($spatialCoverages as $spatialCoverage) {
            // Retrieve dataset version IDs associated with the current spatial coverage
            $datasetVersionIds = DatasetVersionHasSpatialCoverage::where('spatial_coverage_id', $spatialCoverage->id)
                ->whereIn('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the spatial coverage object
            $spatialCoverage->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced spatial coverage to the transformed spatial coverages array
            $transformedSpatialCoverages[] = $spatialCoverage;
        }

        // Return the array of transformed spatial coverages
        return $transformedSpatialCoverages;
    }

    // Accessor for all spatial coverages
    public function getAllSpatialCoveragesAttribute()
    {
        return $this->getAllSpatialCoverages();
    }


    //TOOLS

    /**
     * Get the latest version's tools.
     */
    public function getLatestTools()
    {
        $versionIds = $this->latestVersion()->id;
        $linkage = DatasetVersionHasTool::where('dataset_version_id', $versionIds);
        $toolIds =  $linkage->pluck('tool_id')->unique()->toArray();
        $tools = Tool::whereIn('id', $toolIds)->get();

        // Initialize an array to store transformed tools
        $transformedTools = [];

        // Iterate through each tool and add associated dataset versions
        foreach ($tools as $tool) {
            // Retrieve dataset version IDs associated with the current tool
            $datasetVersionIds = DatasetVersionHasTool::where('tool_id', $tool->id)
                ->where('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the tool object
            $tool->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced tool to the transformed tools array
            $transformedTools[] = $tool;
        }

        // Return the array of transformed tools
        return $transformedTools;
    }

     // Add an accessor for the latest tools
     public function getLatestToolsAttribute()
     {
         return $this->getLatestTools();
     }

     /**
     * Get all tools associated with the latest version.
     */
    public function getAllTools()
    {
        $versionIds = $this->versions()->pluck('id')->toArray();
        $linkage = DatasetVersionHasTool::whereIn('dataset_version_id', $versionIds);
        $toolIds =  $linkage->pluck('tool_id')->unique()->toArray();
        $tools = Tool::whereIn('id', $toolIds)->get();

        // Initialize an array to store transformed tools
        $transformedTools = [];

        // Iterate through each tool and add associated dataset versions
        foreach ($tools as $tool) {
            // Retrieve dataset version IDs associated with the current tool
            $datasetVersionIds = DatasetVersionHasTool::where('tool_id', $tool->id)
                ->whereIn('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the tool object
            $tool->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced tool to the transformed tools array
            $transformedTools[] = $tool;
        }

        // Return the array of transformed tools
        return $transformedTools;
    }

    // Accessor for all tools
    public function getAllToolsAttribute()
    {
        return $this->getAllTools();
    }


    //COLLECTIONS 

    /**
     * Get the latest version's collections.
     */
    public function getLatestCollections()
    {
        $versionIds = $this->latestVersion()->id;
        $linkage = CollectionHasDatasetVersion::where('dataset_version_id', $versionIds);
        $collectionIds =  $linkage->pluck('collection_id')->unique()->toArray();
        $collections = Collection::whereIn('id', $collectionIds)->get();

        // Initialize an array to store transformed collections
        $transformedCollections = [];

        // Iterate through each collection and add associated dataset versions
        foreach ($collections as $collection) {
            // Retrieve dataset version IDs associated with the current collection
            $datasetVersionIds = CollectionHasDatasetVersion::where('collection_id', $collection->id)
                ->where('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the collection object
            $collection->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced collection to the transformed collections array
            $transformedCollections[] = $collection;
        }

        // Return the array of transformed collections
        return $transformedCollections;
    }

    // Add an accessor for the latest collections
    public function getLatestCollectionsAttribute()
    {
        return $this->getLatestCollections();
    }

    /**
     * Get all collections associated with the latest version.
     */
    public function getAllCollections()
    {
        $versionIds = $this->versions()->pluck('id')->toArray();
        $linkage = CollectionHasDatasetVersion::whereIn('dataset_version_id', $versionIds);
        $collectionIds =  $linkage->pluck('collection_id')->unique()->toArray();
        $collections = Collection::whereIn('id', $collectionIds)->get();

        // Initialize an array to store transformed collections
        $transformedCollections = [];

        // Iterate through each collection and add associated dataset versions
        foreach ($collections as $collection) {
            // Retrieve dataset version IDs associated with the current collection
            $datasetVersionIds = CollectionHasDatasetVersion::where('collection_id', $collection->id)
                ->whereIn('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the collection object
            $collection->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced collection to the transformed collections array
            $transformedCollections[] = $collection;
        }

        // Return the array of transformed collections
        return $transformedCollections;
    }

    // Accessor for all collections
    public function getAllCollectionsAttribute()
    {
        return $this->getAllCollections();
    }




     //DURS

    /**
     * Get the latest version's durs.
     */
    public function getLatestDurs()
    {
        $versionIds = $this->latestVersion()->id;
        $linkage = DurHasDatasetVersion::where('dataset_version_id', $versionIds);
        $durIds =  $linkage->pluck('dur_id')->unique()->toArray();
        $durs = Dur::whereIn('id', $durIds)->get();

        // Initialize an array to store transformed durs
        $transformedDurs = [];

        // Iterate through each dur and add associated dataset versions
        foreach ($durs as $dur) {
            // Retrieve dataset version IDs associated with the current dur
            $datasetVersionIds = DurHasDatasetVersion::where('dur_id', $dur->id)
                ->where('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the dur object
            $dur->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced dur to the transformed durs array
            $transformedDurs[] = $dur;
        }

        // Return the array of transformed durs
        return $transformedDurs;
    }

    // Add an accessor for the latest durs
    public function getLatestDursAttribute()
    {
        return $this->getLatestDurs();
    }

    /**
     * Get all durs associated with the latest version.
     */
    public function getAllDurs()
    {
        $versionIds = $this->versions()->pluck('id')->toArray();
        $linkage = DurHasDatasetVersion::whereIn('dataset_version_id', $versionIds);
        $durIds =  $linkage->pluck('dur_id')->unique()->toArray();
        $durs = Dur::whereIn('id', $durIds)->get();

        // Initialize an array to store transformed durs
        $transformedDurs = [];

        // Iterate through each dur and add associated dataset versions
        foreach ($durs as $dur) {
            // Retrieve dataset version IDs associated with the current dur
            $datasetVersionIds = DurHasDatasetVersion::where('dur_id', $dur->id)
                ->whereIn('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the dur object
            $dur->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced dur to the transformed durs array
            $transformedDurs[] = $dur;
        }

        // Return the array of transformed durs
        return $transformedDurs;
    }

    // Accessor for all durs
    public function getAllDursAttribute()
    {
        return $this->getAllDurs();
    }



     // PUBLICATIONS

     /**
     * Get the latest version's publications.
     */
    public function getLatestPublications()
    {
        $versionIds = $this->latestVersion()->id;
        $linkage = PublicationHasDatasetVersion::where('dataset_version_id', $versionIds);
        $publicationIds =  $linkage->pluck('publication_id')->unique()->toArray();
        $publications = Publication::whereIn('id', $publicationIds)->get();

        // Initialize an array to store transformed publications
        $transformedPublications = [];

        // Iterate through each publication and add associated dataset versions
        foreach ($publications as $publication) {
            // Retrieve dataset version IDs associated with the current publication
            $datasetVersionIds = PublicationHasDatasetVersion::where('publication_id', $publication->id)
                ->where('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the publication object
            $publication->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced publication to the transformed publications array
            $transformedPublications[] = $publication;
        }

        // Return the array of transformed publications
        return $transformedPublications;
    }

    // Add an accessor for the latest publications
    public function getLatestPublicationsAttribute()
    {
        return $this->getLatestPublications();
    }

    /**
     * Get all publications associated with the latest version.
     */
    public function getAllPublications()
    {
        $versionIds = $this->versions()->pluck('id')->toArray();
        $linkage = PublicationHasDatasetVersion::whereIn('dataset_version_id', $versionIds);
        $publicationIds =  $linkage->pluck('publication_id')->unique()->toArray();
        $publications = Publication::whereIn('id', $publicationIds)->get();

        // Initialize an array to store transformed publications
        $transformedPublications = [];

        // Iterate through each publication and add associated dataset versions
        foreach ($publications as $publication) {
            // Retrieve dataset version IDs associated with the current publication
            $datasetVersionIds = PublicationHasDatasetVersion::where('publication_id', $publication->id)
                ->whereIn('dataset_version_id', $versionIds)
                ->pluck('dataset_version_id')
                ->toArray();

            // Add associated dataset versions to the publication object
            $publication->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced publication to the transformed publications array
            $transformedPublications[] = $publication;
        }

        // Return the array of transformed publications
        return $transformedPublications;
    }

    // Accessor for all publications
    public function getAllPublicationsAttribute()
    {
        return $this->getAllPublications();
    }

    /**
     * Helper function to use JSON functions to search by title within metadata.
     */
    public function searchByTitle(string $title): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $this->id)
            ->whereRaw(
                "
                LOWER(JSON_EXTRACT(metadata, '$.metadata.summary.title')) LIKE LOWER('%$title%')
                "
            )->latest('version')->first();
    }

    /**
     * Order by raw metadata extract
     */
    public function scopeOrderByMetadata(Builder $query, string $field, string $direction): Builder
    {
        return $query->orderBy(DatasetVersion::selectRaw("JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.".$field."')") 
                            ->whereColumn('datasets.id','dataset_versions.dataset_id')
                            ->latest()->limit(1),$direction);
    }

    /**
     * The team for the dataset.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}