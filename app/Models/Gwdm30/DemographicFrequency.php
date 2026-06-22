<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemographicFrequency extends Model
{
    protected $table = 'gwdm30_demographic_frequencies';

    protected $fillable = [
        'dataset_version_id',
        'category',
        'bin',
        'bin_vocabulary',
        'count',
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
