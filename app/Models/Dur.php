<?php

namespace App\Models;

use App\Models\Team;
use App\Models\User;
use App\Models\Sector;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Application;
use App\Models\Publication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dur extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public $timestamps = true;

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'dur';

    /**
     * The attributes that are mass assignable
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'non_gateway_datasets',
        'non_gateway_applicants',
        'funders_and_sponsors',
        'other_approval_committees',
        'gateway_outputs_tools',
        'gateway_outputs_papers',
        'non_gateway_outputs',
        'project_title',
        'project_id_text',
        'organisation_name',
        'organisation_sector',
        'sector_id',
        'lay_summary',
        'technical_summary',
        'latest_approval_date',
        'manual_upload',
        'rejection_reason',
        'sublicence_arrangements',
        'public_benefit_statement',
        'data_sensitivity_level',
        'project_start_date',
        'project_end_date',
        'access_date',
        'accredited_researcher_status',
        'confidential_data_description',
        'dataset_linkage_description',
        'duty_of_confidentiality',
        'legal_basis_for_data_article6',
        'legal_basis_for_data_article9',
        'national_data_optout',
        'organisation_id',
        'privacy_enhancements',
        'request_category_type',
        'request_frequency',
        'access_type',
        'mongo_object_dar_id',
        'technicalSummary',
        'enabled',
        'last_activity',
        'counter',
        'mongo_object_id',
        'mongo_id',
        'user_id',
        'team_id',
        'created_at', // for migration from mongo database
        'updated_at', // for migration from mongo database
        'applicant_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'manual_upload' => 'boolean',
        'non_gateway_datasets' => 'array',
        'non_gateway_applicants' => 'array',
        'funders_and_sponsors' => 'array',
        'other_approval_committees' => 'array',
        'gateway_outputs_tools' => 'array',
        'gateway_outputs_papers' => 'array',
        'non_gateway_outputs' => 'array',
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

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'dur_has_keywords');
    }

    public function userDatasets(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            DurHasDatasetVersion::class,
            'dur_id', // Foreign key on the CollectionHasDatasetVersion table
            'id',            // Local key on the Collection table
            'id',            // Local key on the User table
            'user_id'        // Foreign key on the CollectionHasDatasetVersion table
        );
    }

    public function applicationDatasets(): HasManyThrough
    {
        return $this->hasManyThrough(
            Application::class,
            DurHasDatasetVersion::class,
            'dur_id', // Foreign key on the CollectionHasDatasetVersion table
            'id',            // Local key on the Collection table
            'id',            // Local key on the Application table
            'application_id' // Foreign key on the CollectionHasDatasetVersion table
        );
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'dur_has_publications')
            ->withPivot('dur_id', 'publication_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at');
    }

    public function userPublications(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dur_has_publications');
    }

    public function applicationPublications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'dur_has_publications');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'dur_has_tools');
    }

    public static function exportHeadings(): array
    {
        return [
            'Non-Gateway Datasets',
            'Non-Gateway Applicants',
            'Funders And Sponsors',
            'Other Approval Committees',
            'Gateway Outputs - Tools',
            'Gateway Outputs - Papers',
            'Non-Gateway Outputs',
            'Project Title',
            'Project ID',
            'Organisation Name',
            'Organisation Sector',
            'Sector ID',
            'Lay Summary',
            'Technical Summary',
            'Latest Approval Date',
            'Manual Upload',
            'Rejection Reason',
            'Sublicence Arrangements',
            'Public Benefit Statement',
            'Data Sensitivity Level',
            'Project Start Date',
            'Project End Data',
            'Access Date',
            'Accredited Researcher Status',
            'Confidential Data Description',
            'Dataset Linkage Description',
            'Duty of Confidentiality',
            'Legal basis for Data Article 6',
            'Legal basis for Data Article 9',
            'National Data Opt-out',
            'Organisation ID',
            'Privacy Enhancements',
            'Request Category Type',
            'Request Frequency',
            'Access Type',
            'DAR ID', // Intentionally renamed to not reveal our internal field names
            'Technical Summary',
            'Enabled',
            'Last Activity',
            'Counter',
            'ID1', // Intentionally renamed to not reveal our internal field names
            'ID2', // Intentionally renamed to not reveal our internal field names
            'UID', // Intentionally renamed to not reveal our internal field names
            'TID', // Intentionally renamed to not reveal our internal field names
            'Created At',
            'Updated At',
            'Applicant ID',
            'Status',
        ];
    }

    /**
     * Retrieve versions associated with this dur
     */
    public function versions()
    {
        return $this->belongsToMany(DatasetVersion::class, 'dur_has_dataset_version', 'dur_id', 'dataset_version_id');
    }

    public function datasetVersions()
    {
        return $this->hasMany(DurHasDatasetVersion::class, 'dur_id');
    }
}
