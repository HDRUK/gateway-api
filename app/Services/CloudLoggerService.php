<?php

namespace App\Services;

use Config;
use Google\Cloud\Logging\LoggingClient;

class CloudLoggerService
{
    protected $logging;
    protected $logger;

    public function __construct()
    {
        $this->logging = null;

        $this->logger = null;
    }

    public function write($data, $severity = 'INFO')
    {
        if (Config::get('services.googlelogging.enabled') === false) {
            return;
        }

        $this->logging = new LoggingClient([
            'projectId' => Config::get('services.googlelogging.project_id'),
        ]);

        $this->logger = $this->logging->logger(Config::get('services.googlelogging.log_name'));

        $message = '';

        $message = is_string($data) ? $data : json_encode($data);

        $entry = $this->logger->entry($message, [
            'severity' => $severity,
            'resource' => ['type' => 'global']
        ]);

        return $this->logger->write($entry);
    }

    public function clearLogging()
    {
        $this->logging = null;
        $this->logger = null;
    }
}
