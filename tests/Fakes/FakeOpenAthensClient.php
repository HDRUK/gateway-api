<?php

namespace Tests\Fakes;

use Jumbojett\OpenIDConnectClient;

/**
 * Stands in for OpenAthens: the code exchange always succeeds and userinfo
 * returns the given JSON, decoded the same way the real client decodes it.
 */
class FakeOpenAthensClient extends OpenIDConnectClient
{
    public function __construct(private readonly string $userInfoJson)
    {
        parent::__construct('https://openathens.test', 'fake-client-id', 'fake-client-secret');
    }

    public function authenticate(): bool
    {
        return true;
    }

    public function requestUserInfo(?string $attribute = null)
    {
        return json_decode($this->userInfoJson, false);
    }
}
