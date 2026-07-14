<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
