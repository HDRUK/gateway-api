<?php

namespace App\Services\Gwdm;

/**
 * Handler for GWDM 2.1.
 *
 * GWDM 2.1 is a small extension of 2.0 — schema-level field differences are
 * handled by TRASER, not here. Extends Gwdm20Handler (rather than the shared
 * Gwdm2xHandler base directly) so that any future 2.0-specific gateway-side
 * fix is inherited by 2.1 automatically; override a method here only when 2.1
 * must deliberately diverge from 2.0's behaviour.
 *
 * Example override candidates when 2.1 diverges:
 *   - buildRequiredBlock() if the required section gains new fields
 *   - buildPublisher() if the publisher object structure changes
 *   - afterStore() if 2.1 introduces structured SQL columns
 */
class Gwdm21Handler extends Gwdm20Handler
{
    // Override methods here when GWDM 2.1 gateway logic diverges from 2.0.
}
