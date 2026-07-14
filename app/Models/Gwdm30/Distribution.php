<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distribution extends Model
{
    protected $table = 'gwdm30_distributions';

    protected $fillable = [
        'dataset_version_id',
        'title',
        'description',
        'access_url',
        'download_url',
        'media_type',
        'format',
        'byte_size',
        'license_url',
        'access_service',
        'issued',
        'modified',
    ];

    protected $casts = [
        'byte_size' => 'integer',
        'issued' => 'date',
        'modified' => 'date',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
