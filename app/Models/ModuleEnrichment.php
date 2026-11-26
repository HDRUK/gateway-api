<?php

namespace App\Models;

use App\Modules\EnrichmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The latest result of one enrichment module for one subject and set of
 * params. Written only through App\Modules\Stores\EloquentEnrichmentStore.
 */
class ModuleEnrichment extends Model
{
    protected $table = 'module_enrichments';

    protected $fillable = [
        'module',
        'enrichable_type',
        'enrichable_id',
        'params',
        'params_hash',
        'partner',
        'status',
        'payload',
        'source_version',
        'reason',
        'fetched_at',
    ];

    protected $casts = [
        'params' => 'array',
        'payload' => 'array',
        'status' => EnrichmentStatus::class,
        'fetched_at' => 'immutable_datetime',
    ];

    public function enrichable(): MorphTo
    {
        return $this->morphTo();
    }
}
