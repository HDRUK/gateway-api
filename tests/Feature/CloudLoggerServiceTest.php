<?php

namespace Tests\Feature;

use Config;
use Mockery;
use Tests\TestCase;
use App\Services\CloudLoggerService;
use Google\Cloud\Logging\LoggingClient;
use Google\Cloud\Logging\Logger;
use Google\Cloud\Logging\Entry;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudLoggerServiceTest extends TestCase
{
    public function test_write_logs_message()
    {
        Config::set('services.googlelogging.project_id', 'fake-project-id');
        Config::set('services.googlelogging.log_name', 'fake-log-name');
        Config::set('services.googlelogging.enabled', true);

        $data = ['key' => 'value'];
        $severity = 'INFO';
        $message = json_encode($data);

        // Mock the Entry class
        $entry = Mockery::mock(Entry::class);

        // Mock the Logger class
        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('entry')
            ->with($message, [
                'severity' => $severity,
                'resource' => ['type' => 'global']
            ])
            ->andReturn($entry);
        $logger->shouldReceive('write')
            ->with($entry)
            ->andReturn(true);

        // Mock the LoggingClient and make it return the mocked Logger
        $loggingClient = Mockery::mock(LoggingClient::class);
        $loggingClient->shouldReceive('logger')
            ->with('fake-log-name')
            ->andReturn($logger);

        // Act
        $service = new CloudLoggerService();
        $reflection = new \ReflectionClass($service);

        $loggingProperty = $reflection->getProperty('logging');
        $loggingProperty->setAccessible(true);
        $loggingProperty->setValue($service, $loggingClient);

        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue($service, $logger);

        $result = $service->write($data, $severity);

        // Assert
        $this->assertTrue($result);
    }
}
