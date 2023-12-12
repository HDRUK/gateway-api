<?php

namespace Tests\Unit;

use App\Mail\Email;
use App\Models\EmailTemplate;

use Tests\TestCase;
use Database\Seeders\EmailTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;


class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            EmailTemplatesSeeder::class,
        ]);
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
            '[[header_text]]' => 'Health Data Research UK',
            '[[button_text]]' => 'Click me!',
            '[[subheading_text]]' => 'Sub Heading Something or other',
        ];

        $email = new Email($template, $replacements);

        Http::fake([
            env('MJML_RENDER_URL') => Http::response([
                    "html" => $this->mockedEmailContent($template,$replacements)
            ], 200),
        ]);

        $html = $email->mjmlToHtml();

        foreach ($replacements as $k => $v) {
            $this->assertStringContainsString($v, $html);
        }
    }

    private function mockedEmailContent(string $template, array $replacements): string
    {
        foreach ($replacements as $placeholder => $replacement) {
            $template = str_replace($placeholder, $replacement, $template);
        }

        return $template;
    }

}

?>
