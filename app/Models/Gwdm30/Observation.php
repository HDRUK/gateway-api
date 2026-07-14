<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $observed_node
 * @property float|null $measured_value
 * @property \Illuminate\Support\Carbon|null $observation_date
 * @property string|null $measured_property
 * @property string|null $disambiguating_description
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
class Observation extends Model
{
    protected $table = 'gwdm30_observations';

    protected $fillable = [
        'dataset_version_id',
        'observed_node',
        'measured_value',
        'observation_date',
        'measured_property',
        'disambiguating_description',
    ];

    protected $casts = [
        'measured_value'   => 'float',
        'observation_date' => 'date',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
