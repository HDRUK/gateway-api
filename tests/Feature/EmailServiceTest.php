<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp([
            EmailTemplateSeeder::class,
        ]);
        $this->seed();

        Bus::fake();
    }

    public function tearDown(): void
    {
        Bus::fake();
        parent::tearDown();
    }

    public function test_dispatch_email_job()
    {
        Mail::fake();

        Http::fake([
            env('MJML_RENDER_URL') => Http::response(
                ["html" => "<html>content</html>"],
                201,
                ['application/json']
            )
        ]);

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

        Bus::assertNothingDispatched();

        SendEmailJob::dispatch($to, $template, $replacements);

        Bus::assertDispatched(SendEmailJob::class);
    }

}
