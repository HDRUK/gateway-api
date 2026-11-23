<?php

namespace Tests\Feature\V3;

use Config;
use Tests\TestCase;
use App\Context\PartnerContext;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Laravel\Pennant\Feature;
use Illuminate\Testing\TestResponse;

class DatasetPartnerContextTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const DATASETS_V3_URL   = '/api/v3/datasets';
    public const TEAMS_URL         = '/api/v1/teams';
    public const NOTIFICATIONS_URL = '/api/v1/notifications';
    public const USERS_URL         = '/api/v1/users';

    protected array $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();

        Feature::flushCache();
        Feature::deactivate(array_values(PartnerContext::CROSS_CONTEXT_FLAGS));
        Feature::deactivate(PartnerContext::SELECTOR_FLAG);
    }

    public function test_v3_update_restamps_cruk_partner_context_when_header_present(): void
    {
        Feature::activate(PartnerContext::SELECTOR_FLAG);
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->header);

        $this->updateViaV3($userId, $datasetId, array_merge($this->header, ['x-partner-context' => 'CRUK']))
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame('CRUK', Dataset::find($datasetId)->partner_context);
    }

    public function test_v3_update_rejects_an_unknown_partner_context(): void
    {
        Feature::activate(PartnerContext::SELECTOR_FLAG);
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, array_merge($this->header, ['x-partner-context' => 'CRUK']));

        $this->updateViaV3($userId, $datasetId, array_merge($this->header, ['x-partner-context' => 'PRUKK']))
            ->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJson(['message' => 'Unknown partner context']);

        $this->assertSame('CRUK', Dataset::find($datasetId)->partner_context);
    }

    public function test_v3_update_leaves_partner_context_when_no_header(): void
    {
        Feature::activate(PartnerContext::SELECTOR_FLAG);
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, array_merge($this->header, ['x-partner-context' => 'CRUK']));

        $this->updateViaV3($userId, $datasetId, $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame('CRUK', Dataset::find($datasetId)->partner_context);
    }

    public function test_v3_update_without_a_header_keeps_a_stored_partner_outside_the_known_list(): void
    {
        Feature::activate(PartnerContext::SELECTOR_FLAG);
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->header);
        Dataset::whereKey($datasetId)->update(['partner_context' => 'cruk']);

        $this->updateViaV3($userId, $datasetId, $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame('cruk', Dataset::find($datasetId)->partner_context);
    }

    public function test_v3_update_ignores_the_header_when_selector_flag_is_off(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, array_merge($this->header, ['x-partner-context' => 'CRUK']));

        $this->updateViaV3($userId, $datasetId, array_merge($this->header, ['x-partner-context' => 'PRUKK']))
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame('CRUK', Dataset::find($datasetId)->partner_context);
    }

    public function test_v3_create_stamps_the_partner_context_from_the_header(): void
    {
        Feature::activate(PartnerContext::SELECTOR_FLAG);
        [$teamId] = $this->createTeamAndUser();

        $response = $this->createViaV3($teamId, array_merge($this->header, ['x-partner-context' => 'CRUK']));

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertSame('CRUK', Dataset::find($response->json('data'))->partner_context);
    }

    public function test_v3_create_rejects_an_unknown_partner_context(): void
    {
        Feature::activate(PartnerContext::SELECTOR_FLAG);
        [$teamId] = $this->createTeamAndUser();

        $this->createViaV3($teamId, array_merge($this->header, ['x-partner-context' => 'PRUKK']))
            ->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJson(['message' => 'Unknown partner context']);

        $this->assertSame(0, Dataset::where('team_id', $teamId)->count());
    }

    public function test_v3_create_ignores_the_header_when_selector_flag_is_off(): void
    {
        [$teamId] = $this->createTeamAndUser();

        $response = $this->createViaV3($teamId, array_merge($this->header, ['x-partner-context' => 'CRUK']));

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertSame('HDRUK', Dataset::find($response->json('data'))->partner_context);
    }

    /**
     * Create a team and user, returning [$teamId, $userId].
     */
    private function createTeamAndUser(): array
    {
        $notificationId = $this->json(
            'POST',
            self::NOTIFICATIONS_URL,
            [
                'notification_type' => 'applicationSubmitted',
                'message'           => 'Test',
                'email'             => null,
                'user_id'           => 3,
                'opt_in'            => 1,
                'enabled'           => 1,
            ],
            $this->header,
        )->json('data');

        $teamId = $this->json(
            'POST',
            self::TEAMS_URL,
            [
                'name'                          => 'Partner Context Test Team ' . uniqid(),
                'enabled'                       => 1,
                'allows_messaging'              => 1,
                'workflow_enabled'              => 1,
                'access_requests_management'    => 1,
                'uses_5_safes'                  => 1,
                'is_admin'                      => 1,
                'member_of'                     => TeamMemberOf::OTHER,
                'contact_point'                 => 'test@example.com',
                'application_form_updated_by'   => 'Test',
                'application_form_updated_on'   => '2023-04-06 15:44:41',
                'notifications'                 => [$notificationId],
                'users'                         => [],
            ],
            $this->header,
        )->json('data');

        $userId = $this->json(
            'POST',
            self::USERS_URL,
            [
                'firstname'        => 'Test',
                'lastname'         => 'User',
                'email'            => 'test.partner.context.' . uniqid() . '@example.com',
                'password'         => 'Passw@rd1!',
                'sector_id'        => 1,
                'organisation'     => 'Test Org',
                'bio'              => 'Bio',
                'domain'           => 'https://example.com',
                'link'             => 'https://example.com/link',
                'orcid'            => 'https://orcid.org/00000000',
                'contact_feedback' => 1,
                'contact_news'     => 1,
            ],
            $this->header,
        )->json('data');

        return [$teamId, $userId];
    }

    private function createViaTeam(int $teamId, int $userId, array $headers): int
    {
        $response = $this->json(
            'POST',
            $this->teamDatasetsUrl($teamId),
            [
                'user_id'       => $userId,
                'metadata'      => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status'        => Dataset::STATUS_ACTIVE,
            ],
            $headers,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $response->json('data');
    }

    private function updateViaV3(int $userId, int $datasetId, array $headers): TestResponse
    {
        return $this->json(
            'PUT',
            self::DATASETS_V3_URL . '/' . $datasetId,
            [
                'user_id'       => $userId,
                'metadata'      => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status'        => Dataset::STATUS_ACTIVE,
            ],
            $headers,
        );
    }

    private function createViaV3(int $teamId, array $headers): TestResponse
    {
        return $this->json(
            'POST',
            '/api/v3/teams/' . $teamId . '/datasets',
            [
                'metadata'      => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status'        => Dataset::STATUS_ACTIVE,
            ],
            $headers,
        );
    }

    private function teamDatasetsUrl(int $teamId): string
    {
        return 'api/v2/teams/' . $teamId . '/datasets';
    }
}
