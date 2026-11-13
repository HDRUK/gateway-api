<?php

namespace Tests\Feature;

use App\Jobs\CreateServiceDeskRequest;
use App\Models\Experiment;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHasRole;
use App\Services\JiraServiceDesk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class ServiceDeskRequestTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const REQUEST_URL = 'https://api.atlassian.com/ex/jira/cloud-1/rest/servicedeskapi/request';

    public function setUp(): void
    {
        $this->commonSetUp();

        config([
            'services.jsm.email' => 'helpdesk-bot@example.com',
            'services.jsm.api_token' => 'token',
            'services.jsm.cloud_id' => 'cloud-1',
            'services.jsm.service_desk_id' => '7',
            'services.jsm.request_type_id' => '78',
        ]);
    }

    private function experiment(): Experiment
    {
        return Experiment::create([
            'name' => 'Search feedback',
            'status' => 'active',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'blocks' => [
                            ['id' => 'intro', 'type' => 'heading', 'text' => 'Hello'],
                            ['id' => 'score', 'type' => 'rating', 'variant' => 'faces', 'max' => 5, 'label' => 'How easy was it?'],
                            [
                                'id' => 'areas',
                                'type' => 'choice',
                                'multiple' => true,
                                'label' => 'Which parts were slow?',
                                'options' => [['id' => 'filters', 'label' => 'Filters'], ['id' => 'results', 'label' => 'Results']],
                            ],
                            ['id' => 'comment', 'type' => 'textInput', 'label' => 'Anything else?'],
                            [
                                'id' => 'layout',
                                'type' => 'abChoice',
                                'label' => 'Which design do you prefer?',
                                'options' => [
                                    [
                                        'id' => 'a',
                                        'label' => 'Design A',
                                        'imageSrc' => 'https://example.com/a.png',
                                        'url' => 'https://example.com/prototype-a',
                                    ],
                                    ['id' => 'b', 'label' => 'Design B', 'imageSrc' => 'https://example.com/b.png'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'targeting' => ['paths' => ['/en/search'], 'audience' => 'all', 'percentage' => 100],
            'trigger' => ['on' => 'load', 'frequency' => 'untilAnswered'],
        ]);
    }

    private function submission(array $overrides = []): array
    {
        return array_merge([
            'experiment_id' => $this->experiment()->id,
            'answers' => ['score' => 2, 'areas' => ['filters', 'results'], 'comment' => 'Filters reset'],
            'path' => '/en/search',
            'signed_in' => false,
            'user_id' => null,
            'user_type' => null,
            'contact_email' => null,
        ], $overrides);
    }

    private function respond(string $path, array $body, array $headers): CreateServiceDeskRequest
    {
        Queue::fake();
        $experiment = $this->experiment();

        $this->json(
            'POST',
            '/api/v1/experiments/' . $experiment->id . '/responses' . $path,
            array_merge(['answers' => ['score' => 4, 'intro' => 'x', 'unknown' => 'y'], 'path' => '/en/search'], $body),
            $headers
        )->assertStatus(201);

        return Queue::pushed(CreateServiceDeskRequest::class)->first();
    }

    private function signedInHeaders(): array
    {
        $this->authorisationUser(false);

        return ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $this->getAuthorisationJwt(false)];
    }

    public function test_a_response_queues_a_request_straight_away_with_only_known_answers(): void
    {
        $job = $this->respond('', [], ['Accept' => 'application/json']);

        $this->assertNull($job->delay);
        $this->assertSame(['score' => 4], $job->submission['answers']);
        $this->assertFalse($job->submission['signed_in']);
        $this->assertNull($job->submission['contact_email']);
    }

    public function test_no_request_is_queued_when_the_service_desk_is_not_configured(): void
    {
        Queue::fake();
        Log::spy();
        config(['services.jsm.api_token' => null]);
        $experiment = $this->experiment();

        $this->json(
            'POST',
            '/api/v1/experiments/' . $experiment->id . '/responses',
            ['answers' => ['score' => 4], 'path' => '/en/search'],
            ['Accept' => 'application/json']
        )->assertStatus(201);

        Queue::assertNotPushed(CreateServiceDeskRequest::class);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context) => str_contains($message, 'JSM is not configured') &&
                $context['experiment_id'] === $experiment->id)
            ->once();
    }

    public function test_creates_a_gateway_feedback_request(): void
    {
        Http::fake([self::REQUEST_URL => Http::response(['issueKey' => 'FBL-101'], 201)]);

        (new CreateServiceDeskRequest($this->submission(['contact_email' => 'user@example.com'])))->handle(app(JiraServiceDesk::class));

        Http::assertSent(function ($request) {
            $fields = $request['requestFieldValues'];

            return $request->url() === self::REQUEST_URL &&
                $request['serviceDeskId'] === '7' &&
                $request['requestTypeId'] === '78' &&
                $fields['summary'] === 'Feedback: Search feedback' &&
                $fields['customfield_10752'] === ['id' => '11728'] &&
                $fields['customfield_11447'] === ['id' => '13138'] &&
                !array_key_exists('customfield_10223', $fields) &&
                str_contains($fields['description'], 'How easy was it?: 2 of 5') &&
                str_contains($fields['description'], 'Which parts were slow?: Filters, Results') &&
                str_contains($fields['description'], 'Anything else?: Filters reset') &&
                str_contains($fields['description'], 'Contact: user@example.com');
        });
    }

    public function test_links_the_chosen_design(): void
    {
        Http::fake([self::REQUEST_URL => Http::response(['issueKey' => 'FBL-111'], 201)]);

        (new CreateServiceDeskRequest($this->submission([
            'answers' => ['layout' => ['optionId' => 'a', 'reason' => 'Clearer']],
        ])))->handle(app(JiraServiceDesk::class));

        Http::assertSent(fn ($request) => $request->url() === self::REQUEST_URL &&
            str_contains(
                $request['requestFieldValues']['description'],
                'Which design do you prefer?: Design A (Clearer) | Image: https://example.com/a.png | Prototype: https://example.com/prototype-a'
            ));
    }

    public function test_says_when_contact_was_not_requested(): void
    {
        Http::fake([self::REQUEST_URL => Http::response(['issueKey' => 'FBL-102'], 201)]);

        (new CreateServiceDeskRequest($this->submission()))->handle(app(JiraServiceDesk::class));

        Http::assertSent(fn ($request) => $request->url() === self::REQUEST_URL &&
            str_contains($request['requestFieldValues']['description'], 'Contact: not requested'));
    }

    public function test_sets_the_user_type(): void
    {
        Http::fake([self::REQUEST_URL => Http::response(['issueKey' => 'FBL-104'], 201)]);

        (new CreateServiceDeskRequest($this->submission(['signed_in' => true, 'user_type' => 'data_custodian'])))->handle(app(JiraServiceDesk::class));

        Http::assertSent(fn ($request) => $request->url() === self::REQUEST_URL &&
            $request['requestFieldValues']['customfield_10223'] === ['id' => '10394']);
    }

    public function test_a_signed_in_response_without_an_opt_in_does_not_identify_the_user(): void
    {
        Http::fake([self::REQUEST_URL => Http::response(['issueKey' => 'FBL-109'], 201)]);

        $job = $this->respond('/me', [], $this->signedInHeaders());
        $job->handle(app(JiraServiceDesk::class));

        $this->assertTrue($job->submission['signed_in']);
        $this->assertNull($job->submission['user_id']);
        $this->assertSame('user', $job->submission['user_type']);
        Http::assertSent(fn ($request) => $request->url() === self::REQUEST_URL &&
            str_contains($request['requestFieldValues']['description'], "Signed in: yes\n"));
    }

    public function test_a_signed_in_opt_in_sends_the_account_email_and_user(): void
    {
        Http::fake([self::REQUEST_URL => Http::response(['issueKey' => 'FBL-107'], 201)]);

        $job = $this->respond('/me', ['contact' => true, 'email' => 'someone-else@example.com'], $this->signedInHeaders());
        $job->handle(app(JiraServiceDesk::class));

        $user = User::where('email', config('constants.test.non_admin.email'))->first();
        Http::assertSent(fn ($request) => $request->url() === self::REQUEST_URL &&
            str_contains($request['requestFieldValues']['description'], 'Contact: ' . $user->email) &&
            str_contains($request['requestFieldValues']['description'], 'Signed in: yes (user ' . $user->id . ')') &&
            !str_contains($request['requestFieldValues']['description'], 'someone-else@example.com'));
    }

    public function test_an_anonymous_opt_in_sends_the_typed_email(): void
    {
        $job = $this->respond('', ['contact' => true, 'email' => 'user@example.com'], ['Accept' => 'application/json']);

        $this->assertSame('user@example.com', $job->submission['contact_email']);
    }

    public function test_an_email_without_an_opt_in_is_not_sent(): void
    {
        $job = $this->respond('', ['contact' => false, 'email' => 'user@example.com'], ['Accept' => 'application/json']);

        $this->assertNull($job->submission['contact_email']);
    }

    public function test_an_answer_of_an_unexpected_shape_does_not_stop_the_request(): void
    {
        Http::fake([self::REQUEST_URL => Http::response(['issueKey' => 'FBL-105'], 201)]);

        (new CreateServiceDeskRequest($this->submission([
            'answers' => ['score' => ['not', 'a', 'number'], 'areas' => [['nested']]],
        ])))->handle(app(JiraServiceDesk::class));

        Http::assertSent(fn ($request) => $request->url() === self::REQUEST_URL);
    }

    public function test_a_rejected_request_is_not_retried(): void
    {
        Http::fake([self::REQUEST_URL => Http::response(['errorMessage' => 'Field customfield_10752 is invalid'], 400)]);

        $job = (new CreateServiceDeskRequest($this->submission()))->withFakeQueueInteractions();
        $job->handle(app(JiraServiceDesk::class));

        $job->assertFailed();
    }

    public function test_a_failure_does_not_keep_the_service_desk_reply(): void
    {
        Http::fake([self::REQUEST_URL => Http::sequence()
            ->push(['errorMessage' => 'Bad value user@example.com'], 400)
            ->push(['errorMessage' => 'Outage near user@example.com'], 503)]);
        $submission = $this->submission(['contact_email' => 'user@example.com']);

        $rejected = (new CreateServiceDeskRequest($submission))->withFakeQueueInteractions();
        $rejected->handle(app(JiraServiceDesk::class));
        $this->assertStringNotContainsString('user@example.com', $rejected->job->failedWith->getMessage());

        try {
            (new CreateServiceDeskRequest($submission))->handle(app(JiraServiceDesk::class));
            $this->fail('Expected the request to throw');
        } catch (\Throwable $exception) {
            $this->assertStringContainsString('503', $exception->getMessage());
            $this->assertStringNotContainsString('user@example.com', $exception->getMessage());
        }
    }

    public function test_a_custodian_is_sent_as_a_data_custodian(): void
    {
        $headers = $this->signedInHeaders();
        UserHasRole::create([
            'user_id' => User::where('email', config('constants.test.non_admin.email'))->value('id'),
            'role_id' => Role::where('name', 'custodian.team.admin')->value('id'),
        ]);

        $job = $this->respond('/me', [], $headers);

        $this->assertSame('data_custodian', $job->submission['user_type']);
    }

    public function test_an_opt_in_uses_the_secondary_email_when_preferred(): void
    {
        $headers = $this->signedInHeaders();
        User::where('email', config('constants.test.non_admin.email'))
            ->update(['preferred_email' => 'secondary', 'secondary_email' => 'secondary@example.com']);

        $job = $this->respond('/me', ['contact' => true], $headers);

        $this->assertSame('secondary@example.com', $job->submission['contact_email']);
    }

    public function test_an_opt_in_falls_back_to_the_primary_email_without_a_secondary(): void
    {
        $headers = $this->signedInHeaders();
        $user = User::where('email', config('constants.test.non_admin.email'))->first();
        $user->update(['preferred_email' => 'secondary', 'secondary_email' => null]);

        $job = $this->respond('/me', ['contact' => true], $headers);

        $this->assertSame($user->email, $job->submission['contact_email']);
    }

    public function test_a_deleted_experiment_is_logged_and_not_sent(): void
    {
        Http::fake();
        Log::spy();
        $submission = $this->submission();
        Experiment::find($submission['experiment_id'])->forceDelete();

        (new CreateServiceDeskRequest($submission))->handle(app(JiraServiceDesk::class));

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context) => str_contains($message, 'experiment no longer exists') &&
                $context['experiment_id'] === $submission['experiment_id'])
            ->once();
    }
}
