<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $access_url
 * @property string|null $download_url
 * @property string|null $media_type
 * @property string|null $format
 * @property int|null $byte_size
 * @property string|null $license_url
 * @property string|null $access_service
 * @property \Illuminate\Support\Carbon|null $issued
 * @property \Illuminate\Support\Carbon|null $modified
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
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
