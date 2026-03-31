<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\ProjectGrantHasDatasetVersionObserver;

#[ObservedBy([ProjectGrantHasDatasetVersionObserver::class])]
class ProjectGrantHasDatasetVersion extends Model
{
    use HasFactory;

    protected $table = 'project_grant_has_dataset_version';

    protected $fillable = [
        'project_grant_version_id',
        'dataset_version_id',
    ];

    public function projectGrantVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectGrantVersion::class, 'project_grant_version_id', 'id');
    }

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class, 'dataset_version_id', 'id');
    }
}
