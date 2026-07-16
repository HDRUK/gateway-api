<?php

namespace App\Context;

use App\Services\Gwdm\GwdmHandlerFactory;
use Config;

/**
 * Resolves the GWDM schema version for write and read operations.
 *
 * targetVersion()   — write path: x-gwdm-version header with config fallback
 * requestedVersion() — read path: x-gwdm-version header only; null if not set
 *
 * These two deliberately handle an unsupported header differently.
 * targetVersion() is called unconditionally by ResolveGwdmVersionContext
 * middleware on every API request (read or write) to seed the request-scoped
 * Context — it must never throw, or a garbage/unsupported header on an
 * unrelated GET would 500 the whole request. It silently falls back to the
 * config default instead. requestedVersion() is only called explicitly by
 * read paths that are asking "did the client request a specific version?" —
 * there, a bad header is a genuine client error, so it throws and the
 * controller turns that into a 400.
 *
 * To add a new supported GWDM version, update GwdmHandlerFactory — the valid
 * version list (SUPPORTED_VERSIONS) lives there as the single source of truth.
 *
 * Used by DatasetService. GMI / MetadataOnboard / MetadataVersioning traits
 * continue using Config::get directly — they always ingest to the system-wide
 * GWDM version regardless of request context.
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

    /**
     * Returns the explicitly requested GWDM version from the request header,
     * or null if the header was not sent. Throws if the header is present but
     * not a recognised version — callers should treat this as a 400.
     *
     * @throws \InvalidArgumentException when an unrecognised version is requested
     */
    public function requestedVersion(): ?string
    {
        $header = request()->header('x-gwdm-version');

        if (! $header) {
            return null;
        }

        if (in_array($header, GwdmHandlerFactory::supportedVersions(), true)) {
            return $header;
        }

        $supported = implode(', ', GwdmHandlerFactory::supportedVersions());
        throw new \InvalidArgumentException(
            "Unsupported GWDM version '{$header}'. Supported versions: {$supported}."
        );
    }
}
