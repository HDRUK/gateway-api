<?php

namespace App\Services;

use Config;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class JiraServiceDesk
{
    private array $config;

    public function __construct()
    {
        $this->config = Config::get('services.jsm');
    }

    public function configured(): bool
    {
        return filled($this->config['email']) && filled($this->config['api_token']) &&
            filled($this->config['cloud_id']) && filled($this->config['service_desk_id']) &&
            filled($this->config['request_type_id']);
    }

    public function createFeedbackRequest(string $summary, string $description, ?string $userType): Response
    {
        $fields = $this->config['fields'];

        $values = [
            'summary' => $summary,
            'description' => $description,
            $fields['enquiry_type']['id'] => ['id' => $fields['enquiry_type']['share_feedback']],
            $fields['future_contact']['id'] => ['id' => $fields['future_contact']['no']],
        ];

        if ($userType) {
            $values[$fields['user_type']['id']] = ['id' => $fields['user_type'][$userType]];
        }

        return Http::withBasicAuth($this->config['email'], $this->config['api_token'])
            ->acceptJson()
            ->post($this->config['base_url'] . '/' . $this->config['cloud_id'] . '/rest/servicedeskapi/request', [
                'serviceDeskId' => (string)$this->config['service_desk_id'],
                'requestTypeId' => (string)$this->config['request_type_id'],
                'requestFieldValues' => $values,
            ]);
    }
}
