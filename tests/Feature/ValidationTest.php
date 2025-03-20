<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\Authorization;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class ValidationTest extends TestCase
{
    use FastRefreshDatabase;
    use Authorization;

    public const TEST_URL = '/api/v1/features';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed([]);
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    public function test_add_new_validation_with_success(): void
    {
        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            [
                'name' => 'fake_for_test',
            ],
            $this->header
        );

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'INVALID_ARGUMENT',
                'message' => 'Invalid argument(s)',
                'errors' => [
                    [
                        'reason' => 'REQUIRED',
                        'message' => 'The enabled field is required.',
                        'field' => 'enabled',
                    ],
                ],
            ]);
    }
}
