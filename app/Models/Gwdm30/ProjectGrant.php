<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGrant extends Model
{
    protected $table = 'gwdm30_project_grants';

    protected $fillable = [
        'dataset_version_id',
        'pid',
        'project_grant_name',
        'lead_researcher',
        'lead_research_institute',
        'grant_number',
        'project_grant_start_date',
        'project_grant_end_date',
        'project_grant_scope',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
