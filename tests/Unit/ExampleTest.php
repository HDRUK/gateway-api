<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Traits\Authorization;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class ExampleTest extends TestCase
{
    use FastRefreshDatabase;
    use Authorization;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([]);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        // $response = $this->json('GET', 'api/v1/features/1', ['Accept' => 'application/json']);
        // $response = $this->get('/');
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->assertTrue(is_string($jwt));
        // $response->assertStatus(200);
    }
}
