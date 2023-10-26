<?php

namespace Tests\Feature;

use Carbon\Carbon;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use App\Exceptions\EmailTemplateException;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

use Tests\TestCase;
use Database\Seeders\EmailTemplatesSeeder;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

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
