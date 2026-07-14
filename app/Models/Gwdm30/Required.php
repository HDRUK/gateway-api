<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Required extends Model
{
    protected $table = 'gwdm30_required';

    protected $fillable = [
        'dataset_version_id',
        'gateway_id',
        'gateway_pid',
        'issued',
        'modified',
        'version',
        'revisions',
    ];

    protected $casts = [
        'issued' => 'datetime',
        'modified' => 'datetime',
        'revisions' => 'array',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
