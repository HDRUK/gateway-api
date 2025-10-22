<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\WithFaker;

class EmailServiceTest extends TestCase
{
    use WithFaker;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

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
            config('MJML_RENDER_URL') => Http::response(
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
