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
     * FALLBACK `summary.publisher` = { publisherId, publisherName } for the
     * legacy GWDM < 1.1 shape. Used by resolvePublisher() only when the payload
     * carries no usable publisher; attributes to the requesting team by pid.
     */
    public function buildPublisher(Team $team): array
    {
        return [
            'publisherId'   => $team->pid,
            'publisherName' => $team->name,
        ];
    }

    /**
     * Legacy GWDM < 1.1 publisher resolution: prefer the raw payload publisher,
     * normalise `publisherId` to a pid (resolving a team by id-or-pid), and fall
     * back to the requesting team when no resolvable publisher is present.
     */
    public function resolvePublisher(array $incoming, Team $team): array
    {
        return $this->resolvePublisherWithKeys($incoming, $team, 'publisherId', 'publisherName');
    }

    public function buildRequiredBlock(Dataset $dataset, int $versionNumber): array
    {
        return [
            'gatewayId'  => strval($dataset->id),
            'gatewayPid' => $dataset->pid,
            'issued'     => $dataset->created,
            'modified'   => $dataset->updated,
            'revisions'  => $this->buildRevisions($dataset, $versionNumber),
            // No 'version' field — not part of GWDM < 1.1 schema
        ];
    }

    public function prepareMetadata(array $gwdm, Dataset $dataset, Team $team, int $versionNumber): array
    {
        $required = $this->buildRequiredBlock($dataset, $versionNumber);
        // DB-derived values win over whatever TRASER returned for the same keys
        $gwdm['required']             = array_merge($gwdm['required'] ?? [], $required);
        $gwdm['summary']['publisher'] = $this->resolvePublisher($gwdm['summary']['publisher'] ?? [], $team);

        return $gwdm;
    }
}
