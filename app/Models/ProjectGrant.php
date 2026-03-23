<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\ProjectGrantObserver;

#[ObservedBy([ProjectGrantObserver::class])]
class ProjectGrant extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;
    protected $table = 'project_grants';

    protected $fillable = [
        'user_id',
        'team_id',
        'version',
        'pid',
        'projectGrantName',
        'leadResearcher',
        'leadResearchInstitute',
        'grantNumbers',
        'projectGrantStartDate',
        'projectGrantEndDate',
        'projectGrantScope',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    protected $casts = [
        'grantNumbers' => 'array',
        'projectGrantStartDate' => 'date',
        'projectGrantEndDate' => 'date',
    ];

    /**
     * Dataset versions that this project grant is associated with.
     */
    public function datasetVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            DatasetVersion::class,
            'project_grant_has_dataset_version',
            'project_grant_id',
            'dataset_version_id'
        );
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(
            Publication::class,
            'project_grant_has_publications',
            'project_grant_id',
            'publication_id'
        )
            ->where('publications.status', 'ACTIVE');
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(
            Tool::class,
            'project_grant_has_tools',
            'project_grant_id',
            'tool_id'
        )
            ->where('tools.status', 'ACTIVE');
    }
}

