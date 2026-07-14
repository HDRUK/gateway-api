<?php

namespace Tests\Feature;

use App\Services\Gwdm\Gwdm1xHandler;
use App\Services\Gwdm\Gwdm20Handler;
use App\Services\Gwdm\Gwdm21Handler;
use App\Services\Gwdm\Gwdm2xHandler;
use App\Services\Gwdm\GwdmHandlerFactory;
use Tests\TestCase;

/**
 * PR2 coverage for the single point of GWDM version branching. This factory is
 * the ONLY place allowed to switch on the schema version; all scattered
 * version_compare() calls in DatasetService were replaced by resolve() here.
 *
 * NOTE: in this PR the 3.0 arm is intentionally absent — GWDM 3.0 is enabled in
 * a later PR of the stack once its handler + SQL tables exist. This test guards
 * that 3.0 is not yet user-selectable so enabling it early cannot 500.
 */
class GwdmHandlerFactoryTest extends TestCase
{
    protected bool $shouldFakeQueue = false;

    private function factory(): GwdmHandlerFactory
    {
        return new GwdmHandlerFactory();
    }

    public function test_resolves_legacy_versions_to_1x_handler(): void
    {
        $this->assertInstanceOf(Gwdm1xHandler::class, $this->factory()->resolve('1.0'));
    }

    public function test_resolves_2_0_and_2_1_to_dedicated_handlers(): void
    {
        $this->assertInstanceOf(Gwdm20Handler::class, $this->factory()->resolve('2.0'));
        $this->assertInstanceOf(Gwdm21Handler::class, $this->factory()->resolve('2.1'));
    }

    public function test_resolves_unknown_1_1_plus_version_to_2x_catch_all(): void
    {
        $this->assertInstanceOf(Gwdm2xHandler::class, $this->factory()->resolve('1.5'));
    }

    public function test_supported_versions_are_2_0_and_2_1_only(): void
    {
        // 3.0 is deliberately NOT yet supported in this PR of the stack.
        $this->assertSame(['2.0', '2.1'], GwdmHandlerFactory::supportedVersions());
        $this->assertNotContains('3.0', GwdmHandlerFactory::supportedVersions());
    }
}
