<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Federation;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\MockExternalApis;

/**
 * Verifies the fixes for Vulns 3 & 4 from the Claude security baseline review:
 *   - Vuln 3: SSRF via unvalidated federation endpoint_baseurl (CreateFederation request)
 *   - Vuln 4: auth_secret_key exposed in Federation API responses (model $hidden)
 */
class ClaudeSecurityReview_Sonnet_46_2 extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];
    protected int $teamId;

    public function setUp(): void
    {
        $this->commonSetUp();
        $this->teamId = $this->createTeamViaApi();
    }

    // -------------------------------------------------------------------------
    // Vuln 3: SSRF — blocked internal addresses
    // -------------------------------------------------------------------------

    /**
     * The AWS instance metadata endpoint (169.254.169.254) is a canonical SSRF
     * target. Submitting it as endpoint_baseurl must be rejected at validation.
     */
    public function test_federation_rejects_aws_metadata_endpoint(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            $this->federationPayload('http://169.254.169.254/latest/meta-data/'),
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        $this->assertStringContainsString(
            'internal or reserved address',
            $response->getContent()
        );
    }

    /**
     * Loopback addresses (127.0.0.1) must be rejected to prevent pivoting to
     * locally bound services such as Redis or internal APIs.
     */
    public function test_federation_rejects_loopback_address(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            $this->federationPayload('http://127.0.0.1:6379/'),
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        $this->assertStringContainsString(
            'internal or reserved address',
            $response->getContent()
        );
    }

    /**
     * RFC-1918 private range 10.x.x.x must be blocked to prevent access to
     * internal network services.
     */
    public function test_federation_rejects_rfc1918_ten_block(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            $this->federationPayload('http://10.0.0.1/internal-api'),
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        $this->assertStringContainsString(
            'internal or reserved address',
            $response->getContent()
        );
    }

    /**
     * RFC-1918 private range 192.168.x.x must be blocked.
     */
    public function test_federation_rejects_rfc1918_192_block(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            $this->federationPayload('https://192.168.1.100/api'),
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        $this->assertStringContainsString(
            'internal or reserved address',
            $response->getContent()
        );
    }

    /**
     * RFC-1918 private range 172.16-31.x.x must be blocked.
     */
    public function test_federation_rejects_rfc1918_172_block(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            $this->federationPayload('http://172.16.0.1/service'),
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        $this->assertStringContainsString(
            'internal or reserved address',
            $response->getContent()
        );
    }

    /**
     * Non-HTTP schemes (e.g. file://) must be blocked regardless of host.
     * The 'url' validation rule rejects non-HTTP(S) schemes before our closure runs.
     */
    public function test_federation_rejects_file_scheme(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            $this->federationPayload('file:///etc/passwd'),
            $this->header
        );

        $this->assertNotEquals(
            Config::get('statuscodes.STATUS_CREATED.code'),
            $response->status(),
            'file:// scheme must not be accepted as a federation endpoint'
        );
    }

    /**
     * A legitimate public HTTPS URL must still be accepted.
     * Uses the same test server URL as the existing FederationTest suite to
     * ensure DNS resolution behaves consistently in all environments.
     */
    public function test_federation_accepts_public_https_url(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            $this->federationPayload('https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app'),
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
    }

    // -------------------------------------------------------------------------
    // Vuln 4: auth_secret_key must not appear in API responses
    // -------------------------------------------------------------------------

    /**
     * auth_secret_key must never appear in the federation index response.
     *
     * Attack scenario (pre-fix): any authenticated user who can list federations
     * would receive the secret in the JSON response, allowing them to impersonate
     * the federation endpoint.
     */
    public function test_federation_index_does_not_expose_auth_secret_key(): void
    {
        // Create a federation that carries a secret.
        $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            array_merge(
                $this->federationPayload('https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app'),
                ['auth_secret_key' => 'super-secret-key-that-must-not-leak']
            ),
            $this->header
        )->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $response = $this->json(
            'GET',
            "api/v1/teams/{$this->teamId}/federations",
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $body = $response->decodeResponseJson();
        $this->assertFederationsDoNotContainSecretKey($body['data']['data'] ?? $body['data'] ?? []);
    }

    /**
     * auth_secret_key must never appear in the federation show response either.
     */
    public function test_federation_show_does_not_expose_auth_secret_key(): void
    {
        $createResponse = $this->json(
            'POST',
            "api/v1/teams/{$this->teamId}/federations",
            array_merge(
                $this->federationPayload('https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app'),
                ['auth_secret_key' => 'super-secret-key-that-must-not-leak']
            ),
            $this->header
        );
        $createResponse->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $federationId = $createResponse->decodeResponseJson()['data'];

        $response = $this->json(
            'GET',
            "api/v1/teams/{$this->teamId}/federations/{$federationId}",
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $data = $response->decodeResponseJson()['data'] ?? [];
        $this->assertArrayNotHasKey(
            'auth_secret_key',
            $data,
            'auth_secret_key must be absent from the federation show response'
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTeamViaApi(): int
    {
        $response = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Security Fed Test Team ' . fake()->regexify('[A-Z]{5}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => TeamMemberOf::HUB,
                'contact_point' => 'security-test@example.com',
                'application_form_updated_by' => 'Security Test',
                'application_form_updated_on' => '2024-01-01 00:00:00',
                'users' => [],
                'notifications' => [],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        return $response->decodeResponseJson()['data'];
    }

    private function federationPayload(string $baseUrl): array
    {
        return [
            'federation_type' => 'federation type',
            'auth_type' => 'NO_AUTH',
            'endpoint_baseurl' => $baseUrl,
            'endpoint_datasets' => '/api/datasets',
            'endpoint_dataset' => '/api/datasets/{id}',
            'run_time_hour' => 2,
            'run_time_minute' => '00',
            'enabled' => true,
            'notifications' => [],
        ];
    }

    private function assertFederationsDoNotContainSecretKey(array $federations): void
    {
        foreach ($federations as $federation) {
            $this->assertArrayNotHasKey(
                'auth_secret_key',
                $federation,
                'auth_secret_key must be absent from all federation list entries'
            );
        }
    }
}
