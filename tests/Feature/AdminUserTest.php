<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\User;
use App\Models\Team;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Review;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\ProjectGrant;
use App\Models\TeamHasUser;
use App\Models\CollectionHasUser;
use App\Models\DataAccessTemplate;
use App\Models\TeamUserHasRole;
use Tests\Traits\Authorization;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\MockExternalApis;

class AdminUserTest extends TestCase
{
    use Authorization;
    use MockExternalApis{
        setUp as commonSetUp;
    }

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * Removing a super-user from a team succeeds and cleans up all three
     * pivot tables.
     *
     * @return void
     */
    public function test_admin_can_remove_super_user_from_team(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        User::where('id', $userId)->update(['is_admin' => 1]);

        $teamHasUser = TeamHasUser::create([
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        $role = \App\Models\Role::first();
        TeamUserHasRole::create([
            'team_has_user_id' => $teamHasUser->id,
            'role_id' => $role->id,
        ]);

        $response = $this->json(
            'DELETE',
            'api/v1/admin/teams/' . $teamId . '/users/' . $userId,
            [],
            $this->header,
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('team_has_users', [
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        $this->assertDatabaseMissing('team_user_has_roles', [
            'team_has_user_id' => $teamHasUser->id,
        ]);

        $this->assertDatabaseMissing('team_user_has_notifications', [
            'team_has_user_id' => $teamHasUser->id,
        ]);
    }

    /**
     * Removing a super-user from multiple teams in one request cleans up
     * every selected team's pivot rows and leaves unselected teams intact.
     *
     * @return void
     */
    public function test_admin_can_remove_super_user_from_multiple_teams(): void
    {
        $teamIdOne = $this->createTeam();
        $teamIdTwo = $this->createTeam();
        $teamIdThree = $this->createTeam();
        $userId = $this->createUser();

        User::where('id', $userId)->update(['is_admin' => 1]);

        TeamHasUser::create(['team_id' => $teamIdOne, 'user_id' => $userId]);
        TeamHasUser::create(['team_id' => $teamIdTwo, 'user_id' => $userId]);
        TeamHasUser::create(['team_id' => $teamIdThree, 'user_id' => $userId]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/remove-from-teams',
            ['team_ids' => [$teamIdOne, $teamIdTwo]],
            $this->header,
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $this->assertEquals('removed', $content['data'][$teamIdOne]);
        $this->assertEquals('removed', $content['data'][$teamIdTwo]);

        $this->assertDatabaseMissing('team_has_users', [
            'team_id' => $teamIdOne,
            'user_id' => $userId,
        ]);
        $this->assertDatabaseMissing('team_has_users', [
            'team_id' => $teamIdTwo,
            'user_id' => $userId,
        ]);
        $this->assertDatabaseHas('team_has_users', [
            'team_id' => $teamIdThree,
            'user_id' => $userId,
        ]);
    }

    /**
     * Removing a non-super-user from teams via this endpoint is rejected.
     *
     * @return void
     */
    public function test_cannot_remove_non_super_user_from_teams_via_bulk_endpoint(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        User::where('id', $userId)->update(['is_admin' => 0]);

        TeamHasUser::create(['team_id' => $teamId, 'user_id' => $userId]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/remove-from-teams',
            ['team_ids' => [$teamId]],
            $this->header,
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('team_has_users', [
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Removing a non-super-user via this endpoint is rejected with 400.
     *
     * @return void
     */
    public function test_admin_cannot_remove_non_super_user_from_team_via_admin_endpoint(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        User::where('id', $userId)->update(['is_admin' => 0]);

        TeamHasUser::create([
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        $response = $this->json(
            'DELETE',
            'api/v1/admin/teams/' . $teamId . '/users/' . $userId,
            [],
            $this->header,
        );

        $response->assertStatus(400);

        $this->assertDatabaseHas('team_has_users', [
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);
    }

    /**
     * A non-super-admin caller is rejected on all three admin routes.
     *
     * @return void
     */
    public function test_non_admin_caller_is_rejected_on_admin_routes(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $this->authorisationUser(false);
        $nonAdminHeader = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAuthorisationJwt(false),
        ];

        $responseTeamUserDelete = $this->json(
            'DELETE',
            'api/v1/admin/teams/' . $teamId . '/users/' . $userId,
            [],
            $nonAdminHeader,
        );
        $responseTeamUserDelete->assertStatus(401);

        $responseDeletionCheck = $this->json(
            'GET',
            'api/v1/admin/users/' . $userId . '/deletion-check',
            [],
            $nonAdminHeader,
        );
        $responseDeletionCheck->assertStatus(401);

        $responseTransferAndDelete = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => []],
            $nonAdminHeader,
        );
        $responseTransferAndDelete->assertStatus(401);
    }

    /**
     * The picker endpoint returns every user's name and team memberships,
     * with no email address in the payload.
     *
     * @return void
     */
    public function test_picker_returns_names_and_teams_without_email(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        TeamHasUser::create([
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        $response = $this->json(
            'GET',
            'api/v1/admin/users/picker',
            [],
            $this->header,
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $entry = collect($content['data'])->firstWhere('id', $userId);

        $this->assertNotNull($entry);
        $this->assertArrayHasKey('firstname', $entry);
        $this->assertArrayHasKey('lastname', $entry);
        $this->assertArrayNotHasKey('email', $entry);
        $this->assertContains($teamId, array_column($entry['teams'], 'id'));
    }

    /**
     * The owned-entity-counts endpoint tallies a user's linked entities in
     * one aggregate pass, keyed by user id.
     *
     * @return void
     */
    public function test_owned_entity_counts_tallies_linked_entities(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();
        $otherUserId = $this->createUser();

        Dataset::factory()->create(['user_id' => $userId, 'team_id' => $teamId]);
        Tool::factory()->create(['user_id' => $userId, 'team_id' => $teamId]);

        $response = $this->json(
            'GET',
            'api/v1/admin/users/owned-entity-counts?user_ids[]=' . $userId . '&user_ids[]=' . $otherUserId,
            [],
            $this->header,
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $this->assertEquals(2, $content['data'][$userId]);
        $this->assertEquals(0, $content['data'][$otherUserId]);
    }

    /**
     * Deletion-check enumerates linked entities correctly for a seeded
     * user with a dataset, tool, and review.
     *
     * @return void
     */
    public function test_deletion_check_enumerates_linked_entities(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $dataset = Dataset::factory()->create([
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        $tool = Tool::factory()->create([
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        $review = Review::factory()->create([
            'user_id' => $userId,
            'tool_id' => $tool->id,
        ]);

        $response = $this->json(
            'GET',
            'api/v1/admin/users/' . $userId . '/deletion-check',
            [],
            $this->header,
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $datasetIds = array_column($content['data']['datasets'], 'id');
        $toolIds = array_column($content['data']['tools'], 'id');
        $reviewIds = array_column($content['data']['reviews'], 'id');

        $this->assertContains($dataset->id, $datasetIds);
        $this->assertContains($tool->id, $toolIds);
        $this->assertContains($review->id, $reviewIds);
    }

    /**
     * Transfer-and-delete rejects a payload missing coverage for a linked
     * entity.
     *
     * @return void
     */
    public function test_transfer_and_delete_rejects_incomplete_coverage(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $dataset = Dataset::factory()->create([
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => []],
            $this->header,
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'user_id' => $userId,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $userId,
        ]);
    }

    /**
     * A Dataset is too heavily linked (versions, DAR applications,
     * collections, DURs, tools) to safely hard-delete here - it must
     * always be reassigned, never deleted, via this endpoint.
     *
     * @return void
     */
    public function test_transfer_and_delete_rejects_dataset_delete(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $dataset = Dataset::factory()->create([
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => [
                ['entity_type' => 'dataset', 'entity_id' => $dataset->id, 'delete' => true],
            ]],
            $this->header,
        );

        // FormRequest-level validation failures return 400 in this app's
        // BaseFormRequest (distinct from the service layer's 422s).
        $response->assertStatus(400);

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'user_id' => $userId,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $userId,
        ]);
    }

    /**
     * A CohortRequest is inherently tied to the specific user who
     * submitted it - it must always be deleted, never reassigned, via
     * this endpoint.
     *
     * @return void
     */
    public function test_transfer_and_delete_rejects_cohort_request_reassignment(): void
    {
        $userId = $this->createUser();
        $newOwnerId = $this->createUser();

        $cohortRequest = \App\Models\CohortRequest::create([
            'user_id' => $userId,
            'request_status' => 'PENDING',
        ]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => [
                ['entity_type' => 'cohort_request', 'entity_id' => $cohortRequest->id, 'new_user_id' => $newOwnerId],
            ]],
            $this->header,
        );

        // FormRequest-level validation failures return 400 in this app's
        // BaseFormRequest (distinct from the service layer's 422s).
        $response->assertStatus(400);

        $this->assertDatabaseHas('cohort_requests', [
            'id' => $cohortRequest->id,
            'user_id' => $userId,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $userId,
        ]);
    }

    /**
     * Transfer-and-delete succeeds: reassigns ownership correctly, nulls
     * out Dur/ProjectGrant/DataAccessTemplate, removes team pivots, and
     * hard-deletes the user.
     *
     * @return void
     */
    public function test_transfer_and_delete_succeeds(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();
        $newOwnerId = $this->createUser('new.owner.' . uniqid() . '@test.com');

        $dataset = Dataset::factory()->create([
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        $tool = Tool::factory()->create([
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        $collection = Collection::factory()->create([
            'team_id' => $teamId,
        ]);
        CollectionHasUser::create([
            'collection_id' => $collection->id,
            'user_id' => $userId,
            'role' => 'CREATOR',
        ]);

        $dur = Dur::create([
            'user_id' => $userId,
            'team_id' => $teamId,
            'project_title' => 'Dur owned by user under test',
        ]);

        $projectGrant = ProjectGrant::create([
            'pid' => 'pg-' . uniqid(),
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        $darTemplate = DataAccessTemplate::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'published' => 0,
            'locked' => 0,
        ]);

        $teamHasUser = TeamHasUser::create([
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        $reassignments = [
            ['entity_type' => 'dataset', 'entity_id' => $dataset->id, 'new_user_id' => $newOwnerId],
            ['entity_type' => 'tool', 'entity_id' => $tool->id, 'delete' => true],
            ['entity_type' => 'collection', 'entity_id' => $collection->id, 'new_user_id' => $newOwnerId],
        ];

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => $reassignments],
            $this->header,
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'user_id' => $newOwnerId,
        ]);

        $this->assertDatabaseMissing('tools', [
            'id' => $tool->id,
        ]);

        $this->assertDatabaseHas('collection_has_users', [
            'collection_id' => $collection->id,
            'user_id' => $newOwnerId,
        ]);

        $this->assertDatabaseHas('dur', [
            'id' => $dur->id,
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('project_grants', [
            'id' => $projectGrant->id,
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('dar_templates', [
            'id' => $darTemplate->id,
            'user_id' => null,
        ]);

        $this->assertDatabaseMissing('team_has_users', [
            'id' => $teamHasUser->id,
        ]);

        $this->assertNull(User::withTrashed()->find($userId));
    }

    /**
     * Deleting a CohortRequest that has child rows in
     * cohort_request_has_logs / cohort_request_has_permissions must not
     * hit a foreign key constraint violation (regression: production
     * 1451 error on cohort_request_has_logs).
     *
     * @return void
     */
    public function test_transfer_and_delete_removes_cohort_request_with_child_rows(): void
    {
        $userId = $this->createUser();

        $cohortRequest = \App\Models\CohortRequest::create([
            'user_id' => $userId,
            'request_status' => 'PENDING',
        ]);

        $cohortRequestLog = \App\Models\CohortRequestLog::create([
            'user_id' => $userId,
            'details' => 'created',
            'request_status' => 'PENDING',
        ]);

        \App\Models\CohortRequestHasLog::create([
            'cohort_request_id' => $cohortRequest->id,
            'cohort_request_log_id' => $cohortRequestLog->id,
        ]);

        $permission = \App\Models\Permission::first();
        \App\Models\CohortRequestHasPermission::create([
            'cohort_request_id' => $cohortRequest->id,
            'permission_id' => $permission->id,
        ]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => [
                ['entity_type' => 'cohort_request', 'entity_id' => $cohortRequest->id, 'delete' => true],
            ]],
            $this->header,
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('cohort_requests', ['id' => $cohortRequest->id]);
        $this->assertDatabaseMissing('cohort_request_has_logs', ['cohort_request_id' => $cohortRequest->id]);
        $this->assertDatabaseMissing('cohort_request_has_permissions', ['cohort_request_id' => $cohortRequest->id]);
        $this->assertNull(User::withTrashed()->find($userId));
    }

    /**
     * Deleting a Tool that has Reviews (from another user) and a
     * Dur<->Tool pivot row must not hit a foreign key constraint
     * violation - neither of those cascades at the DB level.
     *
     * @return void
     */
    public function test_transfer_and_delete_removes_tool_with_reviews_and_dur_link(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();
        $otherUserId = $this->createUser();

        $tool = Tool::factory()->create(['user_id' => $userId, 'team_id' => $teamId]);
        $review = Review::factory()->create(['user_id' => $otherUserId, 'tool_id' => $tool->id]);

        $dur = Dur::create([
            'user_id' => $otherUserId,
            'team_id' => $teamId,
            'project_title' => 'Dur linked to the tool under test',
        ]);
        \App\Models\DurHasTool::create([
            'dur_id' => $dur->id,
            'tool_id' => $tool->id,
        ]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => [
                ['entity_type' => 'tool', 'entity_id' => $tool->id, 'delete' => true],
            ]],
            $this->header,
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tools', ['id' => $tool->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('dur_has_tools', ['tool_id' => $tool->id]);
        $this->assertNull(User::withTrashed()->find($userId));
    }

    /**
     * Deleting an Application detaches (rather than deletes) any Dur
     * records that reference it, since those are independent business
     * records - and removes its own permission/notification pivots.
     *
     * @return void
     */
    public function test_transfer_and_delete_removes_application_and_detaches_dur(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();
        $otherUserId = $this->createUser();

        $application = \App\Models\Application::factory()->create(['user_id' => $userId, 'team_id' => $teamId]);

        $dur = Dur::create([
            'user_id' => $otherUserId,
            'team_id' => $teamId,
            'project_title' => 'Dur linked to the application under test',
        ]);
        // application_id isn't mass-assignable on Dur, so set it directly.
        Dur::where('id', $dur->id)->update(['application_id' => $application->id]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => [
                ['entity_type' => 'application', 'entity_id' => $application->id, 'delete' => true],
            ]],
            $this->header,
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
        $this->assertDatabaseHas('dur', ['id' => $dur->id, 'application_id' => null]);
        $this->assertNull(User::withTrashed()->find($userId));
    }

    /**
     * Deleting an EnquiryThread must also remove its messages, since
     * enquiry_messages.thread_id has no cascade.
     *
     * @return void
     */
    public function test_transfer_and_delete_removes_enquiry_thread_with_messages(): void
    {
        $userId = $this->createUser();

        $thread = \App\Models\EnquiryThread::factory()->create(['user_id' => $userId]);
        $message = \App\Models\EnquiryMessage::create([
            'from' => 'user',
            'message_body' => 'Hello',
            'thread_id' => $thread->id,
        ]);

        $response = $this->json(
            'POST',
            'api/v1/admin/users/' . $userId . '/transfer-and-delete',
            ['reassignments' => [
                ['entity_type' => 'enquiry_thread', 'entity_id' => $thread->id, 'delete' => true],
            ]],
            $this->header,
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('enquiry_threads', ['id' => $thread->id]);
        $this->assertDatabaseMissing('enquiry_messages', ['id' => $message->id]);
        $this->assertNull(User::withTrashed()->find($userId));
    }

    private function createTeam()
    {
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
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
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        $responseNewTeam = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
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
                'application_form_updated_on' => now(),
                'notifications' => [$notificationID],
                'users' => [],
            ],
            $this->header,
        );

        $responseNewTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $responseNewTeam['data'];
    }

    private function createUser(?string $email = null)
    {
        $responseNewUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => $email ? $email : 'firstname.lastname.' . uniqid() . '@test.com',
                'secondary_email' => fake()->unique()->safeEmail(),
                'preferred_email' => 'primary',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => random_int(1, 999999999),
                'mongo_object_id' => fake()->regexify('[a-z0-9]{24}'),
            ],
            $this->header,
        );

        $responseNewUser->assertStatus(201);

        return $responseNewUser['data'];
    }
}
