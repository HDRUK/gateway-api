<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $category
 * @property string|null $bin
 * @property string|null $bin_vocabulary
 * @property int|null $count
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
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
