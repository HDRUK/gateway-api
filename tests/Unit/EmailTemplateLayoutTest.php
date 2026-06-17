<?php

namespace Tests\Unit;

use App\Services\EmailTemplateAssembler;
use Tests\TestCase;

class EmailTemplateLayoutTest extends TestCase
{
    private EmailTemplateAssembler $assembler;

    private const LAYOUTS_DIR = __DIR__ . '/../../database/seeders/email-templates/_layouts';

    private const SENTINEL_HEADER  = 'SENTINEL_HEADER_TEXT_XYZ';
    private const SENTINEL_CONTENT = '<mj-section><mj-column><mj-text>SENTINEL_BODY_CONTENT_XYZ</mj-text></mj-column></mj-section>';

    public function setUp(): void
    {
        parent::setUp();
        $this->assembler = new EmailTemplateAssembler(self::LAYOUTS_DIR);
    }

    // --- standard layout ---

    public function test_standard_layout_produces_valid_mjml_envelope(): void
    {
        $mjml = $this->assembleStandard();

        $this->assertStringContainsString('<mjml>', $mjml);
        $this->assertStringContainsString('</mjml>', $mjml);
        $this->assertStringContainsString('<mj-head>', $mjml);
        $this->assertStringContainsString('<mj-body', $mjml);
        $this->assertStringContainsString('</mj-body>', $mjml);
    }

    public function test_standard_layout_injects_header_text(): void
    {
        $mjml = $this->assembleStandard();

        $this->assertStringContainsString(self::SENTINEL_HEADER, $mjml);
        $this->assertStringNotContainsString('[[HEADER_TEXT]]', $mjml);
    }

    public function test_standard_layout_injects_body_content(): void
    {
        $mjml = $this->assembleStandard();

        $this->assertStringContainsString(self::SENTINEL_CONTENT, $mjml);
        $this->assertStringNotContainsString('[[CONTENT]]', $mjml);
    }

    public function test_standard_layout_contains_structural_signatures(): void
    {
        $mjml = $this->assembleStandard();

        // Font
        $this->assertStringContainsString('Museo Sans Rounded', $mjml);

        // White body
        $this->assertStringContainsString('background-color="#FFFFFF"', $mjml);

        // HDRUK logo header image
        $this->assertStringContainsString('hdruk_logo_email.png', $mjml);

        // Hero banner background image
        $this->assertStringContainsString('hdruk_header_email.png', $mjml);

        // Footer copyright line
        $this->assertStringContainsString('[[CURRENT_YEAR]]', $mjml);
    }

    public function test_standard_layout_header_text_sits_inside_hero_banner(): void
    {
        $mjml = $this->assembleStandard();

        $bannerImagePos = strpos($mjml, 'hdruk_header_email.png');
        $headerTextPos  = strpos($mjml, self::SENTINEL_HEADER);
        $bannerClosePos = strpos($mjml, '</mj-section>', $bannerImagePos);

        $this->assertGreaterThan($bannerImagePos, $headerTextPos, 'Header text should be inside the hero banner section');
        $this->assertLessThan($bannerClosePos, $headerTextPos, 'Header text should be before the hero banner closing tag');
    }

    public function test_standard_layout_order_is_header_then_content_then_footer(): void
    {
        $mjml = $this->assembleStandard();

        $headerPos  = strpos($mjml, self::SENTINEL_HEADER);
        $contentPos = strpos($mjml, 'SENTINEL_BODY_CONTENT_XYZ');
        $footerPos  = strpos($mjml, '[[CURRENT_YEAR]]');

        $this->assertLessThan($contentPos, $headerPos, 'Header text must appear before body content');
        $this->assertLessThan($footerPos, $contentPos, 'Body content must appear before the footer');
    }

    // --- new layout ---

    public function test_new_layout_produces_valid_mjml_envelope(): void
    {
        $mjml = $this->assembleNew();

        $this->assertStringContainsString('<mjml>', $mjml);
        $this->assertStringContainsString('</mjml>', $mjml);
        $this->assertStringContainsString('<mj-head>', $mjml);
        $this->assertStringContainsString('<mj-body', $mjml);
        $this->assertStringContainsString('</mj-body>', $mjml);
    }

    public function test_new_layout_injects_header_text(): void
    {
        $mjml = $this->assembleNew();

        $this->assertStringContainsString(self::SENTINEL_HEADER, $mjml);
        $this->assertStringNotContainsString('[[HEADER_TEXT]]', $mjml);
    }

    public function test_new_layout_injects_body_content(): void
    {
        $mjml = $this->assembleNew();

        $this->assertStringContainsString(self::SENTINEL_CONTENT, $mjml);
        $this->assertStringNotContainsString('[[CONTENT]]', $mjml);
    }

    public function test_new_layout_contains_structural_signatures(): void
    {
        $mjml = $this->assembleNew();

        // Font
        $this->assertStringContainsString('Source Sans 3', $mjml);

        // Grey body
        $this->assertStringContainsString('background-color="#eeeeee"', $mjml);

        // Brand purple used in the top bar
        $this->assertStringContainsString('#475DA7', $mjml);

        // White logo (SVG variant used in new-style templates)
        $this->assertStringContainsString('heath_data_research_gateway_logo_white.svg', $mjml);

        // "Make sure you get these emails" footer block
        $this->assertStringContainsString('Make sure you get these emails in future', $mjml);
        $this->assertStringContainsString('@healthdatagateway.org', $mjml);
    }

    public function test_new_layout_header_text_sits_inside_purple_bar(): void
    {
        $mjml = $this->assembleNew();

        $purpleBarPos   = strpos($mjml, 'background-color="#475DA7"');
        $headerTextPos  = strpos($mjml, self::SENTINEL_HEADER);
        $topEndPos      = strpos($mjml, '<!-- top end -->');

        $this->assertGreaterThan($purpleBarPos, $headerTextPos, 'Header text should be inside the purple bar section');
        $this->assertLessThan($topEndPos, $headerTextPos, 'Header text should be before <!-- top end -->');
    }

    public function test_new_layout_order_is_header_then_content_then_footer(): void
    {
        $mjml = $this->assembleNew();

        $headerPos  = strpos($mjml, self::SENTINEL_HEADER);
        $contentPos = strpos($mjml, 'SENTINEL_BODY_CONTENT_XYZ');
        $footerPos  = strpos($mjml, 'Make sure you get these emails in future');

        $this->assertLessThan($contentPos, $headerPos, 'Header text must appear before body content');
        $this->assertLessThan($footerPos, $contentPos, 'Body content must appear before the footer');
    }

    // --- none layout (raw MJML pass-through) ---

    public function test_none_layout_returns_body_unchanged(): void
    {
        $rawMjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Raw</mj-text></mj-column></mj-section></mj-body></mjml>';

        $result = $this->assembler->assemble(['layout' => 'none', 'body' => $rawMjml]);

        $this->assertSame($rawMjml, $result);
    }

    public function test_missing_layout_key_defaults_to_pass_through(): void
    {
        $rawMjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Raw</mj-text></mj-column></mj-section></mj-body></mjml>';

        $result = $this->assembler->assemble(['body' => $rawMjml]);

        $this->assertSame($rawMjml, $result);
    }

    // --- helpers ---

    private function assembleStandard(): string
    {
        return $this->assembler->assemble([
            'layout'      => 'standard',
            'header_text' => self::SENTINEL_HEADER,
            'body'        => self::SENTINEL_CONTENT,
        ]);
    }

    private function assembleNew(): string
    {
        return $this->assembler->assemble([
            'layout'      => 'new',
            'header_text' => self::SENTINEL_HEADER,
            'body'        => self::SENTINEL_CONTENT,
        ]);
    }
}
