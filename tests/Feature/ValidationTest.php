<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class ValidationTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/features/flush';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

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
