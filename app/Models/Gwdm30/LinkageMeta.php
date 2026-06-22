<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkageMeta extends Model
{
    protected $table = 'gwdm30_linkage_meta';

    protected $fillable = [
        'dataset_version_id',
        'is_generated_using',
        'associated_media',
        'data_uses',
        'is_reference_in',
        'tools',
        'investigations',
        'synthetic_data_web_link',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
