<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coverage extends Model
{
    protected $table = 'gwdm30_coverage';

    protected $fillable = [
        'dataset_version_id',
        'spatial',
        'min_typical_age',
        'max_typical_age',
        'population_coverage',
        'number_of_unique_individuals',
        'number_of_records',
        'pathway',
        'followup',
        'dataset_completeness',
    ];

    protected $casts = [
        'spatial' => 'array',
        'min_typical_age' => 'integer',
        'max_typical_age' => 'integer',
        'number_of_unique_individuals' => 'integer',
        'number_of_records' => 'integer',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
