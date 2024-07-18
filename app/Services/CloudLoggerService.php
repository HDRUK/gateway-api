<?php
namespace App\Services;

use Config;
use Google\Cloud\Logging\LoggingClient;

class CloudLoggerService
{
    protected $logging;
    
    public function __construct()
    {
        $this->logging = new LoggingClient([
            'projectId' => Config::get('services.googlelogging.project_id'),
        ]);
    }

    public function write($data, $severity = 'INFO')
    {
        $message = '';

        if (!Config::get('services.googlelogging.enabled')) {
            return;
        }

        if (is_array($data)) {
            $message = json_encode($data);
        } elseif (is_string($data)) {
            $message = $data;
        } else {
            $message = json_encode($data);
        }

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