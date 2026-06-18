<?php

namespace App\Models\Gwdm30;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DatasetVersion;
use App\Models\Dataset;

/**
 * GWDM 3.0 linkage model — STUB.
 *
 * Single source of truth for dataset linkages on 3.0 rows. Replaces the dual-source
 * problem of dataset_version_has_dataset_version (gateway-tracked) + JSON metadata
 * linkage field (free-text) that exists for GWDM 2.x.
 *
 * Requires migration 2026_06_17_000003 to be uncommented and run.
 *
 * @see \App\Jobs\LinkageExtraction::handleGwdm30()
 * @see \App\Services\DatasetService::getLinkages()
 */
class DatasetVersionLinkage extends Model
{
    protected $table = 'dataset_version_gwdm30_linkages';

    protected $fillable = [
        'dataset_version_id',
        'linkage_type',
        'target_dataset_id',
        'target_title',
        'target_url',
        'is_external',
    ];

    protected $casts = [
        'is_external' => 'boolean',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }

    public function targetDataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class, 'target_dataset_id');
    }
}
