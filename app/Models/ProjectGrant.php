<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectGrant extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;
    protected $table = 'project_grants';

    protected $fillable = [
        'pid',
        'user_id',
        'team_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    /**
     * Versioned snapshots for this grant (same role as dataset_versions for datasets).
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ProjectGrantVersion::class, 'project_grant_id', 'id');
    }

    /**
     * Highest `version` number for this grant (for list views without loading all rows).
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(ProjectGrantVersion::class)->latestOfMany('version');
    }

    /**
     * Datasets this project grant is associated with (stable link, independent of dataset versioning).
     */
    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(
            Dataset::class,
            'project_grant_has_dataset',
            'project_grant_id',
            'dataset_id'
        )->withTimestamps();
    }
}
