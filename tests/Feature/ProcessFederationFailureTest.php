<?php

namespace Tests\Feature;

use App\Events\FederationProcessed;
use App\Events\FederationProcessingFailed;
use App\Jobs\SendEmailCustomIntegration;
use App\Models\Federation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessFederationFailureTest extends TestCase
{
    private function makeFederation(array $overrides = []): Federation
    {
        return Federation::factory()->create(array_merge([
            'enabled' => true,
            'tested' => true,
            'is_running' => true,
            'error' => false,
            'error_text' => null,
        ], $overrides));
    }

    public function test_failure_disables_the_federation_and_records_the_error(): void
    {
        $federation = $this->makeFederation();

        Event::dispatch(new FederationProcessingFailed(
            $federation,
            new \RuntimeException('remote catalogue unreachable'),
            'job-uuid-1'
        ));

        $fresh = $federation->fresh();

        $this->assertFalse($fresh->is_running);
        $this->assertFalse($fresh->enabled);
        $this->assertTrue($fresh->error);
        $this->assertStringContainsString('remote catalogue unreachable', $fresh->error_text);
        Queue::assertPushed(SendEmailCustomIntegration::class, 1);
    }

    public function test_long_error_messages_are_persisted_without_error(): void
    {
        $federation = $this->makeFederation();
        $longMessage = str_repeat('a', 250);

        Event::dispatch(new FederationProcessingFailed(
            $federation,
            new \RuntimeException($longMessage),
            'job-uuid-2'
        ));

        $fresh = $federation->fresh();

        $this->assertTrue($fresh->error);
        $this->assertLessThanOrEqual(200, strlen($fresh->error_text));
    }

    public function test_success_clears_a_previously_recorded_error(): void
    {
        $federation = $this->makeFederation([
            'enabled' => true,
            'is_running' => true,
            'error' => true,
            'error_text' => 'a previous failure',
        ]);

        Event::dispatch(new FederationProcessed($federation, 'job-uuid-3'));

        $fresh = $federation->fresh();

        $this->assertFalse($fresh->error);
        $this->assertNull($fresh->error_text);
    }
}
