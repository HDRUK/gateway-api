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

    // public function test_dispatch_email_job()
    // {
    //     $to = [
    //         'to' => [
    //             'email' => 'loki.sinclair@hdruk.ac.uk',
    //             'name' => 'Loki Sinclair',
    //         ],
    //     ];

    //     $template = EmailTemplate::where('identifier', '=', 'example_template')->first();

    //     $replacements = [
    //         '[[header_text]]' => 'Health Data Research UK',
    //         '[[button_text]]' => 'Click me!',
    //         '[[subheading_text]]' => 'Sub Heading Something or other',
    //     ];

    //     Bus::assertNothingDispatched();

    //     SendEmailJob::dispatch($to, $template, $replacements);

    //     Bus::assertDispatched(SendEmailJob::class);
    // }

    public function testSendEmailJobDispatchesEmailCorrectly()
    {
        Mail::fake();

        Http::fake([
            '*' => Http::response(['html' => '<p>Mocked MJML APi responses HTML Content</p>'], 200),
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

        // Dispatch the job
        Bus::assertNothingDispatched();
        dispatch(new SendEmailJob($to, $template, $replacements));
        Bus::assertDispatched(SendEmailJob::class);

        // Assert an email was sent to the correct recipient with the correct Mailable class
        // Mail::assertSent(Email::class, function ($mail) use ($to) {
        //     return $mail->hasTo($to['to']['email']);
        // });

        // // Assert that the HTTP client was called as expected (optional, depending on your test's focus)
        // Http::assertSent(function ($request) {
        //     return str_contains($request['mjml'], 'Hello, John Doe'); // Assert the request contains the replaced body text
        // });
    }
}
