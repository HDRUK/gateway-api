<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGrantVersionHasTool extends Model
{
    use HasFactory;

    protected $table = 'project_grant_has_tools';

    protected $fillable = [
        'project_grant_version_id',
        'tool_id',
    ];

    public function projectGrantVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectGrantVersion::class, 'project_grant_version_id', 'id');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'tool_id', 'id');
    }
}
