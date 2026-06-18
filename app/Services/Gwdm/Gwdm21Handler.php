<?php

namespace App\Services\Gwdm;

/**
 * Handler for GWDM 2.1.
 *
 * GWDM 2.1 is a small extension of 2.0 — schema-level field differences are
 * handled by TRASER, not here. This handler exists as a dedicated class so that
 * any future 2.1-specific gateway-side logic can be added without touching the
 * shared 2.x base or 2.0's handler.
 *
 * Example override candidates when 2.1 diverges:
 *   - buildRequiredBlock() if the required section gains new fields
 *   - buildPublisher() if the publisher object structure changes
 *   - afterStore() if 2.1 introduces structured SQL columns
 */
class Gwdm21Handler extends Gwdm2xHandler
{
    // Override methods here when GWDM 2.1 gateway logic diverges from 2.x base.
}
