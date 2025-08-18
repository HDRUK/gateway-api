<?php

namespace App\Services;

use Config;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;

class GoogleSecretManagerService
{
    protected $client;

    public function __construct()
    {
        $this->client = new SecretManagerServiceClient();
    }

    public function getSecret(string $secretName, string $version = 'latest'): string
    {
        $projectId = Config::get('metadata.google_project_path');
        $name = $this->client->secretVersionName($projectId, $secretName, $version);

        $request = (new AccessSecretVersionRequest())->setName($name);

        $response = $this->client->accessSecretVersion($request);
        return $response->getPayload()->getData();
    }

    public function createSecret(): void
    {
        // to do
    }
}
