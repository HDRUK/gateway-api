<?php

namespace Tests\Unit\Context;

use App\Context\PartnerContext;
use Tests\TestCase;

class PartnerContextIsKnownTest extends TestCase
{
    public function test_only_hdruk_and_cross_context_partners_are_known(): void
    {
        $this->assertTrue(PartnerContext::isKnown('HDRUK'));
        foreach (array_keys(PartnerContext::CROSS_CONTEXT_FLAGS) as $partner) {
            $this->assertTrue(PartnerContext::isKnown($partner));
        }
        $this->assertFalse(PartnerContext::isKnown('PRUKK'));
        $this->assertFalse(PartnerContext::isKnown(''));
    }
}
