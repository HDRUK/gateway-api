<?php

namespace Tests\Feature;

use App\Models\CohortRequest;
use App\Models\CohortRequestHasPermission;
use App\Models\FeatureFlag;
use App\Models\UserHasWorkgroup;
use App\Models\Workgroup;
use Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Pennant\Feature;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class CohortRequestTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/cohort_requests';

    protected $header = [];

    /**
     * Set up the database
     */
    public function setUp(): void
    {
        $this->commonSetUp();
        $this->runMockHubspot();

        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$jwt,
        ];
    }

    /**
     * Get All Cohort Requests with success
     */
    public function test_get_all_cohort_requests_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'user_id',
                    'user',
                    'request_status',
                    'request_expire_at',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'accept_declaration',
                    'logs',
                    'permissions',
                    'nhse_sde_request_status',
                    'nhse_sde_requested_at',
                    'nhse_sde_self_declared_approved_at',
                    'nhse_sde_updated_at',
                    'nhse_sde_request_expire_at',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Get Cohort Request by id with success
     */
    public function test_get_cohort_request_by_id_with_success(): void
    {
        $randomCohortRequest = CohortRequest::inRandomOrder()->first();
        $randomCohortRequestId = $randomCohortRequest->id;

        $response = $this->json('GET', self::TEST_URL.'/'.$randomCohortRequestId, [], $this->header);

        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Create Cohort Request with success
     */
    public function test_create_cohort_request_with_success(): void
    {
        Mail::fake();

        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // get one
        $responseGetOne = $this->json('GET', self::TEST_URL.'/'.$id, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseGetOne->assertStatus(200);
    }

    /**
     * Update Cohort Request with success
     */
    public function test_update_cohort_request_with_success(): void
    {
        Mail::fake();

        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL.'/'.$id,
            [
                'request_status' => 'APPROVED',
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum - put.',
                'nhse_sde_request_status' => null,
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        // get one
        $responseGetOne = $this->json('GET', self::TEST_URL.'/'.$id, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseGetOne->assertStatus(200);
    }

    public function test_update_cohort_request_with_cds_enabled_success(): void
    {
        Mail::fake();

        Feature::define(FeatureFlag::KEY_COHORT_DISCOVERY_SERVICE, true);
        Feature::flushCache();

        $user = $this->getUserFromJwt($this->getAuthorisationJwt());

        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        Workgroup::truncate();
        $existingWgs = Workgroup::factory(2)->create(['active' => true]);
        $existingWgsIds = [];
        foreach ($existingWgs as $wg) {
            UserHasWorkgroup::create([
                'user_id' => $user['id'],
                'workgroup_id' => $wg->id,
            ]);
            $existingWgsIds[] = $wg->id;
        }

        foreach ($existingWgsIds as $wgId) {
            $this->assertDatabaseHas('user_has_workgroups', [
                'user_id' => $user['id'],
                'workgroup_id' => $wgId,
            ]);
        }

        $newWorkgroups = Workgroup::factory(2)->create(['active' => true]);
        $newWorkgroupsIds = $newWorkgroups->pluck('id')->toArray();

        [$idToKeep, $idToRemove] = $existingWgsIds;

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL.'/'.$id,
            [
                'request_status' => 'APPROVED',
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum - put.',
                'nhse_sde_request_status' => null,
                'workgroup_ids' => [...$newWorkgroupsIds, $idToKeep],
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        // get one
        $responseGetOne = $this->json('GET', self::TEST_URL.'/'.$id, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseGetOne->assertStatus(200);

        //new ids were added
        foreach ($newWorkgroupsIds as $wgId) {
            $this->assertDatabaseHas('user_has_workgroups', [
                'user_id' => $user['id'],
                'workgroup_id' => $wgId,
            ]);
        }

        //existing id was not removed
        $this->assertDatabaseHas('user_has_workgroups', [
            'user_id' => $user['id'],
            'workgroup_id' => $idToKeep,
        ]);

        //old ids were removed
        $this->assertDatabaseMissing('user_has_workgroups', [
            'user_id' => $user['id'],
            'workgroup_id' => $idToRemove,
        ]);

    }

    /**
     * Check request status update and accept declaration
     */
    public function test_request_status_update_and_accept_declaration(): void
    {
        Mail::fake();

        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // Define all possible statuses
        $statuses = ['APPROVED', 'REJECTED', 'BANNED', 'SUSPENDED'];
        $nhseSdeStatuses = [null, 'IN PROCESS', 'APPROVAL REQUESTED', 'APPROVED', 'REJECTED', 'BANNED', 'SUSPENDED'];

        foreach ($statuses as $status) {
            foreach ($nhseSdeStatuses as $nhseSdeStatus) {
                // update
                $responseUpdate = $this->json(
                    'PUT',
                    self::TEST_URL.'/'.$id,
                    [
                        'request_status' => $status,
                        'nhse_sde_request_status' => $nhseSdeStatus,
                        'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum - '.strtolower($status),
                    ],
                    $this->header,
                );

                $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
                    ->assertJsonStructure([
                        'message',
                        'data',
                    ]);

                $contentUpdate = $responseUpdate->decodeResponseJson();
                $this->assertEquals(
                    $contentUpdate['message'],
                    Config::get('statuscodes.STATUS_OK.message')
                );
            }
        }

        // Soft delete
        $responseDelete = $this->json('DELETE', self::TEST_URL.'/'.$id, [], $this->header);

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // Verify accept_declaration is false after soft delete
        $cohortRequest = CohortRequest::withTrashed()->find($id);
        $this->assertFalse($cohortRequest->accept_declaration);
    }

    /**
     * Download Cohort Request Admin dashboard export with success
     */
    public function test_download_cohort_request_dashboard_with_success(): void
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        Mail::fake();

        $responseDownload = $this->json(
            'GET',
            self::TEST_URL.'/export',
            [],
            $this->header,
        );

        $content = $responseDownload->streamedContent();
        $responseDownload->assertHeader('Content-Disposition', 'attachment;filename="Cohort_Discovery_Admin.csv"');
        $this->assertEquals(
            substr($content, 0, 9),
            '"User ID"'
        );
    }

    /**
     * Delete Cohort Request with success
     */
    public function test_delete_cohort_request_with_success(): void
    {
        Mail::fake();

        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL.'/'.$id,
            [
                'request_status' => 'APPROVED',
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum - put.',
                'nhse_sde_request_status' => 'APPROVED',
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        // get one
        $responseGetOne = $this->json('GET', self::TEST_URL.'/'.$id, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseGetOne->assertStatus(200);

        // delete
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL.'/'.$id,
            [],
            $this->header,
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }

    /**
     * Assign / Remove admin permission
     */
    public function test_assign_remove_admin_cohort_request_with_success(): void
    {
        Mail::fake();

        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL.'/'.$id,
            [
                'request_status' => 'APPROVED',
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum - put.',
                'nhse_sde_request_status' => 'APPROVED',
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        // get one
        $responseGetOne = $this->json('GET', self::TEST_URL.'/'.$id, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseGetOne->assertStatus(200);

        // assign admin permission
        $responseAssignAdmin = $this->json(
            'POST',
            self::TEST_URL.'/'.$id.'/admin',
            [],
            $this->header,
        );
        $responseAssignAdmin->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $countPermissions = CohortRequestHasPermission::where(['cohort_request_id' => $id])->count();
        $this->assertTrue((int) $countPermissions === 2);

        // remove admin permission
        $responseAssignAdmin = $this->json(
            'DELETE',
            self::TEST_URL.'/'.$id.'/admin',
            [],
            $this->header,
        );
        $responseAssignAdmin->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $countPermissions = CohortRequestHasPermission::where(['cohort_request_id' => $id])->count();
        $this->assertTrue((int) $countPermissions === 1);

        // delete
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL.'/'.$id,
            [],
            $this->header,
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function runMockHubspot()
    {
        Http::fake([
            // DELETE
            'http://hub.local/contacts/v1/contact/vid/*' => function ($request) {
                if ($request->method() === 'DELETE') {
                    return Http::response([], 200);
                }
            },

            // GET (by vid)
            'http://hub.local/contacts/v1/contact/vid/*/profile' => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response(['vid' => 12345, 'properties' => []], 200);
                } elseif ($request->method() === 'POST') {
                    return Http::response([], 204);
                }
            },

            // GET (by email)
            'http://hub.local/contacts/v1/contact/email/*/profile' => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response(['vid' => 12345], 200);
                }
            },

            // POST (create contact)
            'http://hub.local/contacts/v1/contact' => function ($request) {
                if ($request->method() === 'POST') {
                    return Http::response(['vid' => 12345], 200);
                }
            },
        ]);
    }
}
