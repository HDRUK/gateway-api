<?php

namespace App\Models;

use App\Http\Traits\DatasetFetch;
use App\Models\Traits\EntityCounter;
use App\Models\Traits\SortManager;
use App\Observers\DurObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DurObserver::class])]
class Dur extends Model
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
     * @var list<string>
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

    protected static array $sortableColumns = [
        'created_at',
        'project_title',
        'updated_at',
        'project_start_date',
    ];

    protected static array $countableColumns = [
        'status',
    ];

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getDatasetsViaDatasetVersion(
            new DurHasDatasetVersion(),
            'dur_id'
        );
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'dur_has_keywords');
    }

    // SC: these (userDatasets, applicationDatasets, userPublications, applicationPublications)
    // are not used in the V2 controllers since there would also need to filter by Dataset.status=ACTIVE,
    // and I simply can't make that work. FE doesn't use these fields anyway so chalk it up to minimising
    // BE compute and response size :)
    public function userDatasets(): HasManyThrough //SC: TODO: make this clearer that this is "users via Datasets" rather than "Datasets of the same user"
    {
        return $this->hasManyThrough(
            User::class,
            DurHasDatasetVersion::class,
            'dur_id', // Foreign key on the DurHasDatasetVersion table
            'id',            // Local key on the Dur table
            'id',            // Local key on the User table
            'user_id'        // Foreign key on the DurHasDatasetVersion table
        )
        ->whereNull('dur_has_dataset_version.deleted_at');
    }

    public function applicationDatasets(): HasManyThrough
    {
        return $this->hasManyThrough(
            Application::class,
            DurHasDatasetVersion::class,
            'dur_id', // Foreign key on the DurHasDatasetVersion table
            'id',            // Local key on the Dur table
            'id',            // Local key on the Application table
            'application_id' // Foreign key on the DurHasDatasetVersion table
        )
        ->whereNull('dur_has_dataset_version.deleted_at');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'dur_has_publications')
            ->withPivot('dur_id', 'publication_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at')
            ->whereNull('dur_has_publications.deleted_at')
            ->where('publications.status', 'ACTIVE');
    }

    public function userPublications(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'dur_has_publications'
        )
        ->whereNull('dur_has_publications.deleted_at');
    }

    public function applicationPublications(): BelongsToMany
    {
        return $this->belongsToMany(
            Application::class,
            'dur_has_publications'
        )
        ->whereNull('dur_has_publications.deleted_at');
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
        return $this->belongsToMany(
            Tool::class,
            'dur_has_tools'
        )
        ->whereNull('dur_has_tools.deleted_at')
        ->where('tools.status', 'ACTIVE');
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
        return $this->belongsToMany(
            DatasetVersion::class,
            'dur_has_dataset_version',
            'dur_id',
            'dataset_version_id'
        )
        ->whereNull('dur_has_dataset_version.deleted_at');
    }

    public function datasetVersions(): HasMany
    {
        return $this->hasMany(DurHasDatasetVersion::class, 'dur_id')->whereNull('dur_has_dataset_version.deleted_at');
        ;
    }


    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(
            Collection::class,
            'collection_has_durs',
            'dur_id',
            'collection_id',
        )
        ->whereNull('collection_has_durs.deleted_at')
        ->where('collections.status', 'ACTIVE');
    }

}
