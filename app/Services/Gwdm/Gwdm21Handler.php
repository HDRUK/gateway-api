<?php

namespace App\Services\Gwdm;

/**
 * Handler for GWDM 2.1.
 *
 * Schema-level field differences between 2.0 and 2.1 are handled by TRASER, not
 * here. Extends the shared Gwdm2xHandler base directly (a sibling of, NOT a
 * subclass of, Gwdm20Handler): these handlers encode version-specific
 * compensation logic — required-block shape, publisher format, per-version
 * quirk fixes — which must not leak between versions. A later schema version
 * usually fixes the quirk an earlier handler compensates for, so 2.0's
 * gateway-side behaviour is deliberately NOT inherited by 2.1.
 *
 * Example override candidates when 2.1 diverges from the 2.x base:
 *   - buildRequiredBlock() if the required section gains new fields
 *   - buildPublisher() if the publisher object structure changes
 *   - afterStore() if 2.1 introduces structured SQL columns
 */
class Gwdm21Handler extends Gwdm2xHandler
{
    // Override methods here when GWDM 2.1 gateway logic diverges from the 2.x base.
}
