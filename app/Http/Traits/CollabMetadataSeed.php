<?php

namespace App\Http\Traits;

use App\Models\Team;
use Config;

/**
 * Fields that a normal, authenticated dataset create always gets via
 * MetadataOnboard::metadataOnboard() (version/issued/modified/revisions,
 * and a resolved data custodian) but that collaborative onboarding drafts
 * would otherwise never receive, since draft creation/saving bypasses that
 * flow entirely. Without these, "Make active" fails validation no matter
 * how completely the collaborator fills in the visible form fields.
 */
trait CollabMetadataSeed
{
    /**
     * @param string|null $existingIssued Preserve the original "issued" date
     *   across saves (only set once, on first creation) rather than treating
     *   it like "modified", which should advance on every save.
     */
    private function collabMetadataSeed(Team $team, int $datasetId, ?string $existingIssued = null): array
    {
        return [
            'version' => '1.0.0',
            'issued' => $existingIssued ?? now()->toISOString(),
            'modified' => now()->toISOString(),
            'revisions' => [[
                'version' => '1.0.0',
                'url' => Config::get('gateway.gateway_url') . '/dataset/' . $datasetId . '?version=1.0.0',
            ]],
            'summary' => [
                'dataCustodian' => [
                    'identifier' => $team->pid,
                    'name' => $team->name,
                    'contactPoint' => $team->contact_point,
                ],
            ],
        ];
    }
}
