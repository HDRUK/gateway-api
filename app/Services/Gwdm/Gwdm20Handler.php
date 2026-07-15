<?php

namespace App\Services\Gwdm;

/**
 * Handler for GWDM 2.0.
 *
 * Currently identical to Gwdm2xHandler — exists as a dedicated class so that
 * any future 2.0-specific gateway-side logic (required block changes, publisher
 * field mutations, afterStore hooks) can be added here without touching the
 * shared 2.x base. Gwdm21Handler is a sibling (it extends Gwdm2xHandler
 * directly), so overrides added here apply to 2.0 only and are NOT inherited
 * by 2.1 — version-specific compensation logic must not leak across versions.
 */
class Gwdm20Handler extends Gwdm2xHandler
{
    // Override methods here when GWDM 2.0 gateway logic diverges from the 2.x base.
}
