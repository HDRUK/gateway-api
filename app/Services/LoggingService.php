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
            'projectId' => Config::get('services.googlelogging.project_id'),
        ]);
    }

    public function writeLog(string $message, $severity = 'INFO')
    {
        $logger = $this->logging->logger(Config::get('services.googlelogging.log_name'));
        $entry = $logger->entry($message, [
            'severity' => $severity,
            'resource' => [
                'type' => 'global'
            ]
        ]);

        return $logger->write($entry);
    }
}