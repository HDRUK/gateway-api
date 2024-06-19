<?php
namespace App\Services;

use Config;
use Google\Cloud\Logging\LoggingClient;

class LoggingService
{
    protected $logging;
    
    public function __construct()
    {
        $this->logging = new LoggingClient([
            'projectId' => Config::get('services.googlepubsub.project_id'),
        ]);
    }

    public function writeLog($message, $severity = 'INFO')
    {
        $logger = $this->logging->logger('gateway-api-mk2');
        $entry = $logger->entry($message, [
            'severity' => $severity,
            'resource' => [
                'type' => 'global'
            ]
        ]);

        return $logger->write($entry);
    }
}