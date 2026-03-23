<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGrantHasPublication extends Model
{
    use HasFactory;

    protected $table = 'project_grant_has_publications';

    protected $fillable = [
        'project_grant_id',
        'publication_id',
    ];

    public function projectGrant(): BelongsTo
    {
        return $this->belongsTo(ProjectGrant::class, 'project_grant_id', 'id');
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'publication_id', 'id');
    }
}

