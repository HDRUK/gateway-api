<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
