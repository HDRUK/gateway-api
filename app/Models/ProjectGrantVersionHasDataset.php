<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGrantVersionHasDataset extends Model
{
    use HasFactory;

    protected $table = 'project_grant_has_dataset';

    protected $fillable = [
        'project_grant_id',
        'dataset_id',
    ];

    public function projectGrant(): BelongsTo
    {
        return $this->belongsTo(ProjectGrant::class, 'project_grant_id', 'id');
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class, 'dataset_id', 'id');
    }
}
