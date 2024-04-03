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
        Mail::fake();

        Http::fake([
            env('MJML_RENDER_URL') => Http::response(
                ["html"=>"<html>content</html>"], 
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

        // Mail::assertSent(Email::class, function ($mail) use ($template) {
        //     // var_dump($mail->to[0]['address']);
        //     // exit();
        //     // return $mail->hasTo('loki.sinclair@hdruk.ac.uk') &&
        //     // $mail->subject === 'Example Template' && // Adjust according to your logic
        //     // strpos($mail->mjmlToHtml(), 'body template') === false; // Example assertion on content
        //     return $mail->subject === 'Example Template'; // Example assertion on content
        // });

        Bus::assertDispatched(SendEmailJob::class);
    }

}
