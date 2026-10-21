<?php

namespace Tests\Unit\Context;

use App\Context\PartnerContext;
use Laravel\Pennant\Feature;
use Tests\TestCase;

/**
 * HDRUK always reads its own context, plus each partner context whose
 * RenderSchema<CONTEXT> flag is active:
 *   RenderSchemaCRUK   → CRUK
 *   RenderSchemaPRUK   → PRUK
 *   RenderSchemaCROCOD → CROCOD
 *
 * Non-HDRUK partners are always isolated to their own context.
 */
class PartnerContextReadableContextsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Feature::flushCache();
        Feature::deactivate(array_values(PartnerContext::CROSS_CONTEXT_FLAGS));
    }

    public function test_hdruk_is_restricted_to_itself_when_no_flags_are_active(): void
    {
        $this->assertSame(['HDRUK'], PartnerContext::readableContextsFor('HDRUK'));
    }

    public function test_hdruk_reads_each_context_whose_flag_is_active(): void
    {
        Feature::activate('RenderSchemaCRUK');
        $this->assertSame(['HDRUK', 'CRUK'], PartnerContext::readableContextsFor('HDRUK'));

        Feature::activate('RenderSchemaCROCOD');
        $this->assertSame(['HDRUK', 'CRUK', 'CROCOD'], PartnerContext::readableContextsFor('HDRUK'));
    }

    public function test_hdruk_reads_every_flagged_context_when_all_flags_are_active(): void
    {
        Feature::activate(array_values(PartnerContext::CROSS_CONTEXT_FLAGS));

        $this->assertSame(
            ['HDRUK', 'CRUK', 'PRUK', 'CROCOD'],
            PartnerContext::readableContextsFor('HDRUK'),
        );
    }

    public function test_flags_are_undefined_so_an_unseeded_environment_fails_closed(): void
    {
        Feature::flushCache();
        Feature::purge(array_values(PartnerContext::CROSS_CONTEXT_FLAGS));

        $this->assertSame(['HDRUK'], PartnerContext::readableContextsFor('HDRUK'));
    }

    public function test_non_hdruk_partner_is_always_isolated(): void
    {
        Feature::activate(array_values(PartnerContext::CROSS_CONTEXT_FLAGS));

        $this->assertSame(['CRUK'], PartnerContext::readableContextsFor('CRUK'));
        $this->assertSame(['PRUK'], PartnerContext::readableContextsFor('PRUK'));
        $this->assertSame(['CROCOD'], PartnerContext::readableContextsFor('CROCOD'));
    }

    public function test_instance_method_uses_request_partner(): void
    {
        Feature::activate('RenderSchemaCRUK');
        request()->headers->set('x-partner-context', 'PRUK');

        $this->assertSame(['PRUK'], app(PartnerContext::class)->readableContexts());
    }
}
