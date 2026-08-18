<?php

namespace Tests\Feature\V2;

use App\Http\Enums\TeamMemberOf;
use App\Models\Dataset;
use App\Models\DatasetShareToken;
use App\Models\DatasetVersion;
use Config;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class DatasetShareTokenTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_TEAM = '/api/v1/teams';

    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';

    protected $nonAdminJwt;

    protected $nonAdminUser;

    protected $headerNonAdmin;

    protected $nonAdmin2User;

    protected $headerNonAdmin2;

    protected function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->authorisationUser(false);
        $this->nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->nonAdminUser = $this->getUserFromJwt($this->nonAdminJwt);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->nonAdminJwt,
        ];

        $this->authorisationUser(false, 2);
        $nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        $this->nonAdmin2User = $this->getUserFromJwt($nonAdmin2Jwt);
        $this->headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$nonAdmin2Jwt,
        ];
    }

    private function shareTokensUrl(int $teamId): string
    {
        return 'api/v2/teams/'.$teamId.'/datasets/share-tokens';
    }

    /**
     * A user with the metadata manager role for the team can mint a share
     * token, which also creates an empty, DRAFT/COLLAB-origin dataset.
     */
    public function test_metadata_manager_can_create_share_token(): void
    {
        $notificationId = $this->createNotification();
        $teamId = $this->createTeam([$this->nonAdminUser['id']], [$notificationId]);

        $response = $this->json(
            'POST',
            $this->shareTokensUrl($teamId),
            [],
            $this->headerNonAdmin,
        );

        $response->assertStatus(201);
        $data = $response->decodeResponseJson()['data'];

        $this->assertNotEmpty($data['token']);
        $this->assertEquals($teamId, $data['team_id']);

        $dataset = Dataset::where('id', $data['dataset_id'])->first();
        $this->assertNotNull($dataset);
        $this->assertEquals(Dataset::ORIGIN_COLLAB, $dataset->create_origin);
        $this->assertEquals(Dataset::STATUS_DRAFT, $dataset->status);

        $version = DatasetVersion::where('dataset_id', $dataset->id)->first();
        $this->assertNotNull($version);
    }

    /**
     * Regression test: the skeleton dataset's metadata must already carry
     * the fields normally injected by MetadataOnboard::metadataOnboard()
     * during a standard create - version/issued/modified/revisions, and a
     * resolved data custodian - since collab draft creation bypasses that
     * flow entirely. Without this, "Make active" fails validation later no
     * matter how completely the collaborator fills in the visible fields.
     */
    public function test_created_draft_seeds_backend_owned_metadata_fields(): void
    {
        $notificationId = $this->createNotification();
        $teamId = $this->createTeam([$this->nonAdminUser['id']], [$notificationId]);
        $team = \App\Models\Team::where('id', $teamId)->first();

        $create = $this->json('POST', $this->shareTokensUrl($teamId), [], $this->headerNonAdmin);
        $datasetId = $create->decodeResponseJson()['data']['dataset_id'];

        $dataset = Dataset::where('id', $datasetId)->first();
        $wrapper = $dataset->lastMetadata();

        foreach (['metadata', 'original_metadata'] as $key) {
            $this->assertNotNull($wrapper[$key]['version'] ?? null, "{$key}.version missing");
            $this->assertNotNull($wrapper[$key]['issued'] ?? null, "{$key}.issued missing");
            $this->assertNotNull($wrapper[$key]['modified'] ?? null, "{$key}.modified missing");
            $this->assertNotEmpty($wrapper[$key]['revisions'] ?? [], "{$key}.revisions missing");
            $this->assertEquals(
                $team->pid,
                $wrapper[$key]['summary']['dataCustodian']['identifier'] ?? null,
            );
            $this->assertEquals(
                $team->name,
                $wrapper[$key]['summary']['dataCustodian']['name'] ?? null,
            );
        }
    }

    /**
     * A user with no role on the team cannot mint a share token for it.
     */
    public function test_non_team_member_cannot_create_share_token(): void
    {
        $notificationId = $this->createNotification();
        $teamId = $this->createTeam([$this->nonAdminUser['id']], [$notificationId]);

        $response = $this->json(
            'POST',
            $this->shareTokensUrl($teamId),
            [],
            $this->headerNonAdmin2,
        );

        $response->assertStatus(401);
    }

    public function test_can_list_share_tokens_for_team(): void
    {
        $notificationId = $this->createNotification();
        $teamId = $this->createTeam([$this->nonAdminUser['id']], [$notificationId]);

        $this->json('POST', $this->shareTokensUrl($teamId), [], $this->headerNonAdmin);
        $this->json('POST', $this->shareTokensUrl($teamId), [], $this->headerNonAdmin);

        $response = $this->json('GET', $this->shareTokensUrl($teamId), [], $this->headerNonAdmin);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertCount(2, $response->decodeResponseJson()['data']);
    }

    public function test_can_revoke_share_token(): void
    {
        $notificationId = $this->createNotification();
        $teamId = $this->createTeam([$this->nonAdminUser['id']], [$notificationId]);

        $create = $this->json('POST', $this->shareTokensUrl($teamId), [], $this->headerNonAdmin);
        $tokenId = $create->decodeResponseJson()['data']['id'];

        $response = $this->json(
            'DELETE',
            $this->shareTokensUrl($teamId).'/'.$tokenId,
            [],
            $this->headerNonAdmin,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $token = DatasetShareToken::where('id', $tokenId)->first();
        $this->assertNotNull($token->revoked_at);
        $this->assertFalse($token->isValid());
    }

    private function createNotification()
    {
        $response = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header,
        );

        return $response->decodeResponseJson()['data'];
    }

    private function createTeam(array $userIds, array $notificationIds)
    {
        $response = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test '.fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => $notificationIds,
                'users' => $userIds,
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $teamId = $response->decodeResponseJson()['data'];

        foreach ($userIds as $userId) {
            $this->json(
                'PUT',
                'api/v1/teams/'.$teamId.'/users/'.$userId,
                [
                    'roles' => [
                        'custodian.metadata.manager' => true,
                    ],
                ],
                $this->header,
            )->assertStatus(200);
        }

        return $teamId;
    }
}
