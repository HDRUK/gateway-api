<?php

namespace App\Context;

use App\Services\Gwdm\GwdmHandlerFactory;
use Config;

/**
 * Resolves the GWDM schema version to target for write operations.
 *
 * Resolution order (highest priority first):
 *   1. x-gwdm-version request header  — per-request override
 *   2. Config::get('metadata.GWDM.version')  — env-driven system default (GWDM_CURRENT_VERSION)
 *
 * To add a new supported GWDM version, update GwdmHandlerFactory — the valid
 * version list (SUPPORTED_VERSIONS) lives there as the single source of truth.
 *
 * Used by DatasetService (V3 write path only). GMI / MetadataOnboard /
 * MetadataVersioning traits continue using Config::get directly — they always
 * ingest to the system-wide GWDM version regardless of request context.
 */
class GwdmVersionContext
{
    public function targetVersion(): string
    {
        $header = request()->header('x-gwdm-version');

        if ($header && in_array($header, GwdmHandlerFactory::supportedVersions(), true)) {
            return $header;
        }

        return Config::get('metadata.GWDM.version', '2.0');
    }
}
