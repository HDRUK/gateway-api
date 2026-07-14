<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $filter_id
 * @property string|null $label
 * @property string|null $category
 * @property string|null $primary_group
 * @property string|null $description
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
class DatasetFilter extends Model
{
    protected $table = 'gwdm30_dataset_filters';

    protected $fillable = [
        'dataset_version_id',
        'filter_id',
        'label',
        'category',
        'primary_group',
        'description',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
