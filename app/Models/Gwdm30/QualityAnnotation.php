<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $annotation_type
 * @property string|null $quality_dimension
 * @property string|null $quality_value
 * @property string|null $quality_description
 * @property string|null $certification_url
 * @property \Illuminate\Support\Carbon|null $annotation_date
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
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
