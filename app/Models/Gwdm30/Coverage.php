<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property array|null $spatial
 * @property int|null $min_typical_age
 * @property int|null $max_typical_age
 * @property string|null $population_coverage
 * @property int|null $number_of_unique_individuals
 * @property int|null $number_of_records
 * @property string|null $pathway
 * @property string|null $followup
 * @property string|null $dataset_completeness
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
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
