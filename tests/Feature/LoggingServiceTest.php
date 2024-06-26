<?php

namespace Tests\Feature;

use Mockery;
use App\Services\LoggingService;
use Google\Cloud\Logging\LoggingClient;
use Google\Cloud\Logging\Logger;
use Google\Cloud\Logging\Entry;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LoggingServiceTest extends TestCase
{
    protected $loggingService;
    protected $loggingClientMock;
    protected $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();

        Config::shouldReceive('get')
            ->with('services.googlelogging.project_id')
            ->andReturn('test-project-id');

        Config::shouldReceive('get')
            ->with('services.googlelogging.log_name')
            ->andReturn('test-log-name');

        $this->loggingClientMock = $this->createMock(LoggingClient::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->loggingService = new class($this->loggingClientMock) extends LoggingService {
            public function __construct($loggingClient)
            {
                $this->logging = $loggingClient;
            }
        };
    }

    public function testGcpLoggingWriteLog()
    {
        $message = 'Test log message';
        $severity = 'INFO';

        $entryMock = $this->createMock(Entry::class);

        $this->loggerMock->expects($this->once())
            ->method('entry')
            ->with($message, [
                'severity' => $severity,
                'resource' => [
                    'type' => 'global'
                ]
            ])
            ->willReturn($entryMock);

        $this->loggerMock->expects($this->once())
            ->method('write')
            ->with($entryMock)
            ->willReturn(true);

        $this->loggingClientMock->expects($this->once())
            ->method('logger')
            ->with('test-log-name')
            ->willReturn($this->loggerMock);

        $result = $this->loggingService->writeLog($message, $severity);

        $this->assertTrue($result);
    }
}
