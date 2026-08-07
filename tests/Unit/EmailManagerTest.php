<?php

namespace Tests\Unit;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use App\Services\EmailManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailManagerTest extends TestCase
{
    private function to(): array
    {
        return [
            'to' => [
                'email' => 'user@example.com',
                'name' => 'Jane Doe',
            ],
        ];
    }

    public function test_send_dispatches_job_when_template_is_enabled(): void
    {
        $template = EmailTemplate::where('identifier', '=', 'example_template')->first();
        $this->assertNotNull($template, 'Expected seeded example_template to exist');
        $this->assertTrue($template->enabled);

        $sent = app(EmailManager::class)->send('example_template', $this->to(), []);

        $this->assertTrue($sent);
        Queue::assertPushed(SendEmailJob::class, 1);
    }

    public function test_send_returns_false_and_logs_when_template_is_disabled(): void
    {
        Log::spy();

        EmailTemplate::create([
            'identifier' => 'test.email_manager.disabled',
            'enabled' => false,
            'subject' => 'Disabled template',
            'body' => '<p>Disabled</p>',
        ]);

        $sent = app(EmailManager::class)->send('test.email_manager.disabled', $this->to(), []);

        $this->assertFalse($sent);
        Queue::assertNothingPushed();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'disabled'));
    }

    public function test_send_returns_false_and_logs_when_template_is_missing(): void
    {
        Log::spy();

        $sent = app(EmailManager::class)->send('test.email_manager.does_not_exist', $this->to(), []);

        $this->assertFalse($sent);
        Queue::assertNothingPushed();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'not found'));
    }
}
