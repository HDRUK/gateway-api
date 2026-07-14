<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $pid
 * @property string|null $project_grant_name
 * @property string|null $lead_researcher
 * @property string|null $lead_research_institute
 * @property string|null $grant_number
 * @property string|null $project_grant_start_date
 * @property string|null $project_grant_end_date
 * @property string|null $project_grant_scope
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
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
