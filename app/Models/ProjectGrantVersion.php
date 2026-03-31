<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\ProjectGrantVersionObserver;

#[ObservedBy([ProjectGrantVersionObserver::class])]
class ProjectGrantVersion extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 'project_grant_versions';

    protected $fillable = [
        'project_grant_id',
        'version',
        'projectGrantName',
        'leadResearcher',
        'leadResearchInstitute',
        'grantNumbers',
        'projectGrantStartDate',
        'projectGrantEndDate',
        'projectGrantScope',
    ];

    protected $casts = [
        'grantNumbers' => 'array',
        'projectGrantStartDate' => 'date',
        'projectGrantEndDate' => 'date',
    ];

    public function projectGrant(): BelongsTo
    {
        return $this->belongsTo(ProjectGrant::class, 'project_grant_id', 'id');
    }

    public function projectGrantHasDatasetVersions(): HasMany
    {
        return $this->hasMany(ProjectGrantHasDatasetVersion::class, 'project_grant_version_id', 'id');
    }

    public function datasetVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            DatasetVersion::class,
            'project_grant_has_dataset_version',
            'project_grant_version_id',
            'dataset_version_id'
        );
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(
            Publication::class,
            'project_grant_has_publications',
            'project_grant_version_id',
            'publication_id'
        )
            ->where('publications.status', 'ACTIVE');
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(
            Tool::class,
            'project_grant_has_tools',
            'project_grant_version_id',
            'tool_id'
        )
            ->where('tools.status', 'ACTIVE');
    }
}
