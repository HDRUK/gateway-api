<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetVersionProvenance extends Model
{
    protected $table = 'gwdm30_provenance';

    protected $fillable = [
        'dataset_version_id',
        'origin_purpose',
        'origin_source',
        'origin_collection_situation',
        'temporal_start_date',
        'temporal_end_date',
        'temporal_time_lag',
        'temporal_accrual_periodicity',
    ];

    protected $casts = [
        'temporal_start_date' => 'date',
        'temporal_end_date'   => 'date',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
