<?php

namespace App\Services;

class CloudLoggerService
{
    private $logLevels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    public function __construct()
    {

    }

    public function write($data, $severity = 'INFO')
    {
        $message = is_string($data) ? $data : json_encode($data);
        $output = strtolower($severity);

        if (!in_array($output, $this->logLevels)) {
            \Log::error($output . ' is not in known logging levels of "' . implode(', ', $this->logLevels) .
                '" for message: ' . $message);
        }

        \Log::$output($message);
    }
}
