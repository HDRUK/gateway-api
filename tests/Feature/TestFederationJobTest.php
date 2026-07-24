<?php

namespace Tests\Feature;

use App\Jobs\TestFederation;
use App\Services\GoogleSecretManagerService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class TestFederationJobTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const BASE_URL = 'https://test-federation.example.com';
    private const DATASETS_PATH = '/api/v1/datasets';
    private const DATASET_PATH = '/api/v1/datasets/{id}';

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function makeInput(): array
    {
        return [
            'auth_type' => 'NO_AUTH',
            'auth_secret_key' => null,
            'endpoint_baseurl' => self::BASE_URL,
            'endpoint_datasets' => self::DATASETS_PATH,
            'endpoint_dataset' => self::DATASET_PATH,
            'run_time_hour' => 11,
        ];
    }

    /**
     * Regression test: pullCatalogueList() was declared to return a strict
     * Collection, but its array-federation branch (used here, since
     * TestFederation passes an array not a Federation model) can legitimately
     * return a plain array describing a failed connection attempt. That
     * mismatch threw "Return value must be of type Collection, array
     * returned" any time a federation test hit a non-200 remote response —
     * exactly the case a user testing a broken/misconfigured federation
     * would hit.
     */
    public function test_handle_returns_failure_array_instead_of_throwing_on_non_200_response(): void
    {
        $this->mock(GoogleSecretManagerService::class);

        Http::fake([
            self::BASE_URL . self::DATASETS_PATH . '*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $result = (new TestFederation($this->makeInput()))->handle();

        $this->assertFalse($result['data']['success']);
        $this->assertEquals(401, $result['data']['status']);
        $this->assertEquals('Test Unsuccessful', $result['data']['title']);
    }

    public function test_handle_returns_success_payload_on_200_response(): void
    {
        $this->mock(GoogleSecretManagerService::class);

        Http::fake([
            self::BASE_URL . self::DATASETS_PATH . '*' => Http::response(['items' => []], 200),
        ]);

        $result = (new TestFederation($this->makeInput()))->handle();

        $this->assertTrue($result['data']['success']);
        $this->assertEquals(200, $result['data']['status']);
        $this->assertEquals('Test Successful', $result['data']['title']);
    }
}
