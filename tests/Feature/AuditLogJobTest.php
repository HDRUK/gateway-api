<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Jobs\AuditLogJob;
use App\Services\CloudLoggerService;
use App\Services\CloudPubSubService;

class AuditLogJobTest extends TestCase
{
    public function test_audit_log_job_sends_message_and_logs()
    {
        Config::set('GOOGLE_CLOUD_PROJECT_ID', 'fake-project-id');
        Config::set('GOOGLE_CLOUD_PUBSUB_TOPIC', 'fake-topic-name');
        Config::set('GOOGLE_CLOUD_PUBSUB_ENABLED', false);
        Config::set('GOOGLE_CLOUD_LOGGING_ENABLED', false);

        // Arrange
        $auditLogData = ['key' => 'value'];

        // Mock CloudPubSubService
        $cloudPubSubMock = $this->createMock(CloudPubSubService::class);
        $cloudPubSubMock->expects($this->once())
            ->method('publishMessage')
            ->with($auditLogData)
            ->willReturn('message-id');

        // Mock CloudLoggerService
        $cloudLoggerMock = $this->createMock(CloudLoggerService::class);
        $cloudLoggerMock->expects($this->once())
            ->method('write')
            ->with('Message sent to pubsub from "SendAuditLogToPubSub" job "message-id"');

        // Act
        $job = new AuditLogJob($auditLogData);
        $job->handle($cloudPubSubMock, $cloudLoggerMock);

        // Assert
        // Verify that publishMessage was called with the correct data
        $this->assertTrue(
            method_exists($cloudPubSubMock, 'publishMessage'),
            'Method publishMessage does not exist on CloudPubSubService mock'
        );

        // Verify that write was called with the correct log message
        $this->assertTrue(
            method_exists($cloudLoggerMock, 'write'),
            'Method write does not exist on CloudLoggerService mock'
        );
    }
}
