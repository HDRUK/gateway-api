<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\Team;

/**
 * Handler for GWDM versions < 1.1 (legacy).
 *
 * Differences from 2.x:
 *   - summary.publisher uses the OLD format: { publisherId, publisherName }
 *   - required block does NOT include a 'version' field
 *
 * This handler exists to avoid any version_compare calls in DatasetService.
 * In practice, no new datasets should be created with GWDM < 1.1; this handles
 * legacy ingestion paths only.
 */
class Gwdm1xHandler extends GwdmMetadataHandler
{
    /**
     * Build the legacy summary.publisher = { publisherId, publisherName }.
     *
     * Keeps the publisher from the incoming metadata payload when its publisherId
     * maps to an existing team (validated by casting to an integer team id and
     * looking it up); otherwise falls back to the requesting team.
     *
     * KNOWN ISSUE: publisherId here is the raw team primary key, not the team's
     * persistent id (pid). Properly resolved/normalised on
     * fix/GAT-9018-publisher-fix.
     */
    public function buildPublisher(Team $team, array $incoming = []): array
    {
        $publisherId = $incoming['publisherId'] ?? null;

        // Valid only if the publisherId maps to an existing team (by integer id).
        if ($publisherId !== null && Team::find((int) $publisherId)) {
            return $incoming;
        }

        return [
            'publisherId' => (string) $team->id,
            'publisherName' => $team->name,
        ];
    }

    public function buildRequiredBlock(Dataset $dataset, int $versionNumber): array
    {
        return [
            'gatewayId' => strval($dataset->id),
            'gatewayPid' => $dataset->pid,
            'issued' => $dataset->created,
            'modified' => $dataset->updated,
            'revisions' => $this->buildRevisions($dataset, $versionNumber),
            // No 'version' field — not part of GWDM < 1.1 schema
        ];
    }

    public function prepareMetadata(array $gwdm, Dataset $dataset, Team $team, int $versionNumber): array
    {
        $required = $this->buildRequiredBlock($dataset, $versionNumber);
        // DB-derived values win over whatever TRASER returned for the same keys
        $gwdm['required'] = array_merge($gwdm['required'] ?? [], $required);
        $gwdm['summary']['publisher'] = $this->buildPublisher($team, $gwdm['summary']['publisher'] ?? []);

        return $gwdm;
    }

    /**
     * Legacy GWDM (< 1.1) has no tissuesSampleCollection section, so no
     * material types are exposed.
     */
    public function getMaterialTypes(array $metadata): ?array
    {
        return null;
    }
}
