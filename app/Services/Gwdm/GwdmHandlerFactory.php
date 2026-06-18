<?php

namespace App\Services\Gwdm;

/**
 * Resolves a GWDM version string to the appropriate metadata lifecycle handler.
 *
 * This is the ONLY place in the codebase that branches on the GWDM schema
 * version for write-path operations. All scattered version_compare() calls
 * in DatasetService have been replaced by a single handler resolution here.
 *
 * Resolution rules:
 *   < 1.1  → Gwdm1xHandler  (legacy publisher field format, no required.version)
 *   2.0    → Gwdm20Handler  (extends Gwdm2xHandler; override here when 2.0 diverges)
 *   2.1    → Gwdm21Handler  (extends Gwdm2xHandler; override here when 2.1 diverges)
 *   3.0    → Gwdm30Handler  (extends Gwdm2xHandler; SQL structured table hooks)
 *   else   → Gwdm2xHandler  (catch-all for any 1.1+ version without a dedicated class)
 *
 * To add a new GWDM version:
 *   1. Create a new handler class extending the most appropriate parent
 *   2. Add a match arm to resolve()
 *   3. Add the version string to SUPPORTED_VERSIONS — GwdmVersionContext will
 *      pick it up automatically for x-gwdm-version header validation.
 */
class GwdmHandlerFactory
{
    /**
     * All GWDM versions that are valid targets for the x-gwdm-version request
     * header. This is the single source of truth — GwdmVersionContext reads
     * from here rather than maintaining its own duplicate list.
     *
     * Note: legacy versions handled internally by the factory (e.g. < 1.1)
     * are not listed here because they are not user-selectable via the header.
     */
    public const SUPPORTED_VERSIONS = ['2.0', '2.1', '3.0'];

    /** @return string[] */
    public static function supportedVersions(): array
    {
        return self::SUPPORTED_VERSIONS;
    }

    public function resolve(string $version): GwdmMetadataHandler
    {
        return match (true) {
            version_compare($version, '1.1', '<') => new Gwdm1xHandler($version),
            $version === '2.0'                     => new Gwdm20Handler($version),
            $version === '2.1'                     => new Gwdm21Handler($version),
            $version === '3.0'                     => new Gwdm30Handler($version),
            default                                => new Gwdm2xHandler($version),
        };
    }
}
