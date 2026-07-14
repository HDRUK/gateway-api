<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Erd extends Model
{
    protected $table = 'gwdm30_erd';

    protected $fillable = [
        'dataset_version_id',
        'description',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
