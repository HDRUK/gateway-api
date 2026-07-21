<?php

namespace App\Services;

use Config;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\AddSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\CreateSecretRequest;
use Google\Cloud\SecretManager\V1\DeleteSecretRequest;
use Google\Cloud\SecretManager\V1\Replication;
use Google\Cloud\SecretManager\V1\Replication\Automatic;
use Google\Cloud\SecretManager\V1\Secret;
use Google\Cloud\SecretManager\V1\SecretPayload;

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

    public function createSecret(string $secretId, string $payload): void
    {
        $projectPath = Config::get('metadata.google_project_path');

        $secret = (new Secret())->setReplication(
            (new Replication())->setAutomatic(new Automatic())
        );

        $request = (new CreateSecretRequest())
            ->setParent($projectPath)
            ->setSecretId($secretId)
            ->setSecret($secret);

        $this->client->createSecret($request);

        $this->addSecretVersion($secretId, $payload);
    }

    public function addSecretVersion(string $secretId, string $payload): void
    {
        $projectPath = Config::get('metadata.google_project_path');

        $request = (new AddSecretVersionRequest())
            ->setParent($projectPath . '/secrets/' . $secretId)
            ->setPayload((new SecretPayload())->setData($payload));

        $this->client->addSecretVersion($request);
    }

    public function deleteSecret(string $secretId): void
    {
        $projectPath = Config::get('metadata.google_project_path');

        $request = (new DeleteSecretRequest())
            ->setName($projectPath . '/secrets/' . $secretId);

        $this->client->deleteSecret($request);
    }
}
