<?php

namespace Tests\Unit;

use App\Mail\Email;
use App\Models\EmailTemplate;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Http;

class EmailServiceTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * Tests that the email service can replace placeholder text
     * within an mjml encoded email body
     *
     * @return void
     */
    public function test_the_email_service_replaces_text(): void
    {
        $to = [
            'to' => [
                'email' => 'loki.sinclair@hdruk.ac.uk',
                'name' => 'Loki Sinclair',
            ],
        ];

        $template = EmailTemplate::where('identifier', '=', 'example_template')->first();

        $replacements = [
            '[[HEADER_TEXT]]' => 'Health Data Research UK',
            '[[SUBHEADING_TEXT]]' => 'Sub Heading Something or other',
            '[[BUTTON_1_URL]]' => 'https://test.com/something1',
            '[[BUTTON_2_URL]]' => 'https://test.com/something2',
        ];

        $email = new Email($template, $replacements);

        Http::fake([
            env('MJML_RENDER_URL') => Http::response([
                    "html" => $this->mockedEmailContent($template, $replacements)
            ], 200),
        ]);

        $html = $email->mjmlToHtml();

        foreach ($replacements as $k => $v) {
            $this->assertStringContainsString($v, $html);
        }

        $this->assertStringNotContainsString('[[HEADER_TEXT]]', $html);
        $this->assertStringNotContainsString('[[SUBHEADING_TEXT]]', $html);
        $this->assertStringNotContainsString('[[BUTTON_1_URL]]', $html);
        $this->assertStringNotContainsString('[[BUTTON_2_URL]]', $html);
    }

    private function mockedEmailContent(string $template, array $replacements): string
    {
        foreach ($replacements as $placeholder => $replacement) {
            $template = str_replace($placeholder, $replacement, $template);
        }

        return $template;
    }

}
