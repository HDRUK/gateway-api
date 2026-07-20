<?php

namespace Tests\Feature;

use App\Context\GwdmVersionContext;
use Config;
use Tests\TestCase;

/**
 * Covers the GWDM version request context — the read/write header split that
 * the handler-resolution refactor pivots on.
 *
 * Guards two things: requestedVersion() must return null when the
 * x-gwdm-version header is absent (so the read path falls back to the
 * dataset's genuine latest version, not a config default), and the
 * deliberate asymmetry between the write path (targetVersion(), config
 * fallback) and the read path (requestedVersion(), null on absent header).
 */
class GwdmVersionContextTest extends TestCase
{
    protected bool $shouldFakeQueue = false;

    private function context(): GwdmVersionContext
    {
        return new GwdmVersionContext();
    }

    private function setHeader(?string $value): void
    {
        if ($value === null) {
            request()->headers->remove('x-gwdm-version');

            return;
        }

        request()->headers->set('x-gwdm-version', $value);
    }

    public function test_target_version_falls_back_to_config_when_header_absent(): void
    {
        Config::set('metadata.GWDM.version', '2.1');
        $this->setHeader(null);

        // Write path: no header -> configured system-wide GWDM version.
        $this->assertSame('2.1', $this->context()->targetVersion());
    }

    public function test_target_version_uses_supported_header_when_present(): void
    {
        Config::set('metadata.GWDM.version', '2.0');
        $this->setHeader('2.1');

        $this->assertSame('2.1', $this->context()->targetVersion());
    }

    public function test_target_version_ignores_unsupported_header_and_falls_back(): void
    {
        Config::set('metadata.GWDM.version', '2.0');
        $this->setHeader('9.9');

        // An unrecognised write header is ignored (not fatal) -> config fallback.
        $this->assertSame('2.0', $this->context()->targetVersion());
    }

    public function test_requested_version_returns_null_when_header_absent(): void
    {
        Config::set('metadata.GWDM.version', '2.1');
        $this->setHeader(null);

        // Read path must NOT substitute the config default — null signals the
        // caller to use the genuine latest version of the dataset.
        $this->assertNull($this->context()->requestedVersion());
    }

    public function test_requested_version_returns_supported_header(): void
    {
        $this->setHeader('2.0');

        $this->assertSame('2.0', $this->context()->requestedVersion());
    }

    public function test_requested_version_throws_on_unsupported_header(): void
    {
        $this->setHeader('9.9');

        $this->expectException(\InvalidArgumentException::class);
        $this->context()->requestedVersion();
    }
}
