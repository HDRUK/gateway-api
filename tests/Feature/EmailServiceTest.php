<?php

namespace Tests\Feature;

use Carbon\Carbon;
use App\Mail\Email;
use Tests\TestCase;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Bus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\EmailTemplateException;
use Database\Seeders\EmailTemplatesSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp([
            EmailTemplatesSeeder::class,
        ]);
        $this->seed();

        Bus::fake();
    }

    public function tearDown(): void
    {
        Bus::fake();
    }

    public function test_dispatch_email_job()
    {
        // Http::fake();
        $applicationKey = env('MJML_API_APPLICATION_KEY');
        $apiKey = env('MJML_API_KEY');
        $basicAuth = base64_encode("{$applicationKey}:{$apiKey}");

        Http::fake([
            'api.mjml.io/*' => Http::response([
                'html' => '<p>Your HTML content here</p>',
            ], 200),
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

        // $username = env('MJML_API_APPLICATION_KEY');
        // $password = env('MJML_API_KEY');

        SendEmailJob::dispatch($to, $template, $replacements);

        // Http::assertSent(function ($request) use ($basicAuth) {
        //     return $request->hasHeader('Authorization', "Basic {$basicAuth}");
        // });

        Bus::assertDispatched(SendEmailJob::class);
    }

}
