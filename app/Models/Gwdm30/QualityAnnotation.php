<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityAnnotation extends Model
{
    protected $table = 'gwdm30_quality_annotations';

    protected $fillable = [
        'dataset_version_id',
        'annotation_type',
        'quality_dimension',
        'quality_value',
        'quality_description',
        'certification_url',
        'annotation_date',
    ];

    protected $casts = [
        'annotation_date' => 'date',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
