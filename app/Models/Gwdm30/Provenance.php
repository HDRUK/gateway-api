<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Provenance extends Model
{
    protected $table = 'gwdm30_provenance';

    protected $fillable = [
        'dataset_version_id',
        'origin_purpose',
        'origin_source',
        'origin_collection_situation',
        'origin_image_contrast',
        'temporal_start_date',
        'temporal_end_date',
        'temporal_time_lag',
        'temporal_accrual_periodicity',
        'temporal_distribution_release_date',
        'retention_period_start',
        'retention_period_end',
    ];

    protected $casts = [
        'origin_purpose' => 'array',
        'origin_source' => 'array',
        'origin_collection_situation' => 'array',
        'temporal_start_date' => 'date',
        'temporal_end_date' => 'date',
        'temporal_distribution_release_date' => 'date',
        'retention_period_start' => 'date',
        'retention_period_end' => 'date',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
