<?php

namespace Tests\Unit\Context;

use App\Context\PartnerContext;
use Tests\TestCase;

/**
 * partners.allow_cross_context_read accepts:
 *   true                → HDRUK reads every context (unrestricted, null)
 *   false               → HDRUK reads only HDRUK
 *   'HDRUK,CRUK'        → HDRUK reads the listed contexts (HDRUK always included)
 *
 * Non-HDRUK partners are always isolated to their own context.
 */
class PartnerContextReadableContextsTest extends TestCase
{
    public function test_hdruk_is_unrestricted_when_flag_is_true(): void
    {
        config(['partners.allow_cross_context_read' => true]);

        $this->assertNull(PartnerContext::readableContextsFor('HDRUK'));
    }

    public function test_hdruk_is_restricted_to_itself_when_flag_is_false(): void
    {
        config(['partners.allow_cross_context_read' => false]);

        $this->assertSame(['HDRUK'], PartnerContext::readableContextsFor('HDRUK'));
    }

    public function test_hdruk_reads_comma_delimited_list(): void
    {
        config(['partners.allow_cross_context_read' => 'HDRUK,CRUK']);

        $this->assertSame(['HDRUK', 'CRUK'], PartnerContext::readableContextsFor('HDRUK'));
    }

    public function test_list_is_trimmed_deduplicated_and_always_includes_hdruk(): void
    {
        config(['partners.allow_cross_context_read' => ' CRUK , PRUK,,CRUK ']);

        $this->assertSame(['HDRUK', 'CRUK', 'PRUK'], PartnerContext::readableContextsFor('HDRUK'));
    }

    public function test_string_booleans_are_honoured(): void
    {
        config(['partners.allow_cross_context_read' => 'false']);
        $this->assertSame(['HDRUK'], PartnerContext::readableContextsFor('HDRUK'));

        config(['partners.allow_cross_context_read' => 'true']);
        $this->assertNull(PartnerContext::readableContextsFor('HDRUK'));
    }

    public function test_non_hdruk_partner_is_always_isolated(): void
    {
        config(['partners.allow_cross_context_read' => true]);
        $this->assertSame(['CRUK'], PartnerContext::readableContextsFor('CRUK'));

        config(['partners.allow_cross_context_read' => 'HDRUK,CRUK,PRUK']);
        $this->assertSame(['PRUK'], PartnerContext::readableContextsFor('PRUK'));
    }

    public function test_instance_method_uses_request_partner(): void
    {
        config(['partners.allow_cross_context_read' => false]);
        request()->headers->set('x-partner-context', 'CRUK');

        $this->assertSame(['CRUK'], app(PartnerContext::class)->readableContexts());
    }
}
