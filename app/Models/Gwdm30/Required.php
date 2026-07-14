<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $gateway_id
 * @property string|null $gateway_pid
 * @property \Illuminate\Support\Carbon|null $issued
 * @property \Illuminate\Support\Carbon|null $modified
 * @property string|null $version
 * @property array|null $revisions
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
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
