<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetVersionSummary extends Model
{
    protected $table = 'gwdm30_summary';

    protected $fillable = [
        'dataset_version_id',
        'abstract',
        'contact_point',
        'keywords',
        'controlled_keywords',
        'dataset_type',
        'description',
        'doi_name',
        'publisher_name',
        'publisher_gateway_id',
        'population_size',
    ];

    protected $casts = [
        'controlled_keywords' => 'array',
        'population_size'     => 'integer',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
