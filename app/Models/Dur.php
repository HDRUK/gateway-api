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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dur extends Model
{
    use HasFactory, SoftDeletes, Prunable;

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

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'dur_has_keywords');
    }

    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'dur_has_datasets')
            ->withPivot('dur_id', 'dataset_id', 'user_id', 'application_id', 'is_locked', 'reason', 'created_at', 'updated_at');
    }

    public function userDatasets(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dur_has_datasets');
    }

    public function applicationDatasets(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'dur_has_datasets');
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
        ];
    }
}
