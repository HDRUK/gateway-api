<?php

namespace App\Services\Gwdm;

/**
 * Handler for GWDM 2.0.
 *
 * Currently identical to Gwdm2xHandler — exists as a dedicated class so that
 * any future 2.0-specific gateway-side logic (required block changes, publisher
 * field mutations, afterStore hooks) can be added here without touching the
 * shared 2.x base or 2.1's handler.
 */
class Gwdm20Handler extends Gwdm2xHandler
{
    // Override methods here when GWDM 2.0 gateway logic diverges from 2.x base.
}
