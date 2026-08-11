<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetLinkCheckResult extends Model
{
    protected $table = 'dataset_link_check_results';

    protected $fillable = [
        'dataset_id',
        'team_id',
        'team_name',
        'url',
        'status_code',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
