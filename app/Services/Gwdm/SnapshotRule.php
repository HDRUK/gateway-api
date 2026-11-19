<?php

namespace App\Services\Gwdm;

class SnapshotRule
{
    public const INTERVAL = 10;

    public static function shouldSnapshot(int $version, ?string $previousGwdmVersion, string $targetGwdmVersion): bool
    {
        return $version === 1
            || $version % self::INTERVAL === 0
            || $previousGwdmVersion !== $targetGwdmVersion;
    }
}
