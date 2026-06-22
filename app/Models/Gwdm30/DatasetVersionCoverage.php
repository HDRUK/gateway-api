<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetVersionCoverage extends Model
{
    protected $table = 'gwdm30_coverage';

    protected $fillable = [
        'dataset_version_id',
        'spatial',
        'typical_age_range',
        'pathway',
        'followup',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
