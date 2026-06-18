<?php

namespace App\Models\Gwdm30;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DatasetVersion;

/**
 * GWDM 3.0 accessibility model — STUB.
 *
 * Structured SQL storage for accessibility fields currently JSON-extracted in
 * FormHydrationController::getDefaultValues() via DEFAULTS_PATHS.
 *
 * When this model is active, FormHydrationController should read from here
 * rather than walking JSON dot-paths — no path map needed for 3.0.
 *
 * Requires migration 2026_06_17_000003 to be uncommented and run.
 */
class DatasetVersionAccessibility extends Model
{
    protected $table = 'dataset_version_gwdm30_accessibility';

    protected $fillable = [
        'dataset_version_id',
        'data_use_limitation',
        'data_use_requirements',
        'access_rights',
        'access_service',
        'access_request_cost',
        'delivery_lead_time',
        'formats',
    ];

    protected $casts = [
        'data_use_limitation'   => 'array',
        'data_use_requirements' => 'array',
        'formats'               => 'array',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
