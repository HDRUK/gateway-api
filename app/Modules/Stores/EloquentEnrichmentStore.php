<?php

namespace App\Modules\Stores;

use App\Models\ModuleEnrichment;
use App\Modules\Contracts\EnrichmentStore;
use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentResult;
use App\Modules\EnrichmentStatus;

/**
 * Keeps the latest result per module, subject and params in
 * `module_enrichments`. Skipped and Missing results describe a read, not a
 * pull, so they are never written.
 */
class EloquentEnrichmentStore implements EnrichmentStore
{
    public function find(string $module, EnrichmentContext $context): ?EnrichmentResult
    {
        $row = ModuleEnrichment::query()
            ->where($this->key($module, $context))
            ->first();

        if ($row === null) {
            return null;
        }

        return new EnrichmentResult(
            status: $row->status,
            payload: $row->payload ?? [],
            sourceVersion: $row->source_version,
            reason: $row->reason,
            module: $row->module,
            fetchedAt: $row->fetched_at,
        );
    }

    public function put(EnrichmentContext $context, EnrichmentResult $result): void
    {
        if (in_array($result->status, [EnrichmentStatus::Skipped, EnrichmentStatus::Missing], true)) {
            return;
        }

        ModuleEnrichment::updateOrCreate($this->key($result->module, $context), [
            'params' => $context->params,
            'partner' => $context->owningPartner(),
            'status' => $result->status,
            'payload' => $result->payload,
            'source_version' => $result->sourceVersion,
            'reason' => $result->reason,
            'fetched_at' => $result->fetchedAt,
        ]);
    }

    private function key(string $module, EnrichmentContext $context): array
    {
        return [
            'module' => $module,
            'enrichable_type' => $context->subjectType,
            'enrichable_id' => $context->subjectId,
            'params_hash' => $context->paramsHash(),
        ];
    }
}
