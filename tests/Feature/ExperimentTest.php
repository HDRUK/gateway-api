<?php

namespace Tests\Feature;

use App\Models\Experiment;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class ExperimentTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/experiments';

    private const SESSION_ID = 'web: 3f2b8c1e-6d4a-4b7e-9a0c-2e5f7d1b8a64';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Onboarding layout',
            'status' => 'active',
            'definition' => [
                'steps' => [
                    [
                        'id' => 'step-1',
                        'blocks' => [
                            [
                                'id' => 'score',
                                'type' => 'rating',
                                'variant' => 'faces',
                                'max' => 5,
                                'label' => 'How easy was it?',
                            ],
                            [
                                'id' => 'intro',
                                'type' => 'heading',
                                'text' => 'Hello',
                            ],
                        ],
                    ],
                ],
            ],
            'targeting' => [
                'paths' => ['/en/search'],
                'audience' => 'all',
                'percentage' => 100,
            ],
            'trigger' => [
                'on' => 'load',
                'frequency' => 'untilAnswered',
            ],
            'starts_at' => null,
            'ends_at' => null,
        ], $overrides);
    }

    private function userHeader(): array
    {
        $this->authorisationUser(false);

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAuthorisationJwt(false),
        ];
    }

    public function test_superadmin_can_create_update_and_delete_an_experiment(): void
    {
        $response = $this->json('POST', self::TEST_URL, $this->payload(), $this->header);
        $response->assertStatus(201);
        $id = $response->json('data');

        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            $this->payload(['name' => 'Renamed', 'status' => 'paused']),
            $this->header
        );
        $response->assertStatus(200);
        $this->assertSame('Renamed', $response->json('data.name'));

        $response = $this->json('GET', self::TEST_URL, [], $this->header);
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id, 'responses_count' => 0]);

        $this->json('DELETE', self::TEST_URL . '/' . $id, [], $this->header)->assertStatus(200);
        $this->assertSoftDeleted('experiments', ['id' => $id]);
    }

    public function test_non_admin_cannot_manage_experiments(): void
    {
        $this->json('GET', self::TEST_URL, [], $this->userHeader())->assertStatus(401);
    }

    public function test_create_rejects_an_invalid_audience(): void
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            $this->payload(['targeting' => ['audience' => 'everyone']]),
            $this->header
        );

        $response->assertStatus(400);
    }

    public function test_create_accepts_an_exit_intent_trigger(): void
    {
        $this->json(
            'POST',
            self::TEST_URL,
            $this->payload(['trigger' => ['on' => 'exitIntent', 'frequency' => 'once']]),
            $this->header
        )->assertStatus(201);
    }

    public function test_active_only_returns_running_experiments_for_the_audience(): void
    {
        $public = Experiment::create($this->payload());
        $signedIn = Experiment::create($this->payload(['targeting' => ['audience' => 'logged_in']]));
        $chosen = Experiment::create($this->payload([
            'targeting' => ['audience' => 'user_ids', 'user_ids' => [999999]],
        ]));
        $draft = Experiment::create($this->payload(['status' => 'draft']));
        $ended = Experiment::create($this->payload(['ends_at' => now()->subDay()->toDateString()]));

        $anonymous = $this->json('GET', self::TEST_URL . '/active', [], ['Accept' => 'application/json']);
        $anonymous->assertStatus(200);
        $anonymousIds = collect($anonymous->json('data'))->pluck('id');
        $this->assertContains($public->id, $anonymousIds);
        $this->assertNotContains($signedIn->id, $anonymousIds);
        $this->assertNotContains($draft->id, $anonymousIds);
        $this->assertNotContains($ended->id, $anonymousIds);

        $me = $this->json('GET', self::TEST_URL . '/active/me', [], $this->userHeader());
        $meIds = collect($me->json('data'))->pluck('id');
        $this->assertContains($signedIn->id, $meIds);
        $this->assertNotContains($chosen->id, $meIds);
        $me->assertJsonMissingPath('data.0.targeting');
    }

    public function test_active_includes_the_end_date(): void
    {
        $experiment = Experiment::create($this->payload(['ends_at' => '2099-01-31']));

        $active = collect($this->json('GET', self::TEST_URL . '/active', [], ['Accept' => 'application/json'])->json('data'))
            ->firstWhere('id', $experiment->id);

        $this->assertSame('2099-01-31', $active['endsAt']);
    }

    public function test_percentage_assignment_is_stable_for_a_session(): void
    {
        $experiment = Experiment::create($this->payload(['targeting' => ['percentage' => 50]]));
        $headers = ['Accept' => 'application/json', 'x-request-session-id' => self::SESSION_ID];

        $first = collect($this->json('GET', self::TEST_URL . '/active', [], $headers)->json('data'))
            ->pluck('id')->contains($experiment->id);
        $second = collect($this->json('GET', self::TEST_URL . '/active', [], $headers)->json('data'))
            ->pluck('id')->contains($experiment->id);

        $this->assertSame($first, $second);
        $this->assertSame(crc32($experiment->id . ':' . self::SESSION_ID) % 100 < 50, $first);
    }

    public function test_a_response_is_counted_but_not_stored(): void
    {
        $experiment = Experiment::create($this->payload());

        $this->json(
            'POST',
            self::TEST_URL . '/' . $experiment->id . '/responses',
            ['answers' => ['score' => 4], 'path' => '/en/search'],
            ['Accept' => 'application/json', 'x-request-session-id' => self::SESSION_ID]
        )->assertStatus(201)->assertJsonPath('data', $experiment->id);

        $this->assertSame(1, $experiment->fresh()->responses_count);
        $this->assertFalse(Schema::hasTable('experiment_responses'));
    }

    public function test_response_to_an_inactive_experiment_is_not_found(): void
    {
        $experiment = Experiment::create($this->payload(['status' => 'paused']));

        $this->json(
            'POST',
            self::TEST_URL . '/' . $experiment->id . '/responses',
            ['answers' => ['score' => 4], 'path' => '/en/search'],
            ['Accept' => 'application/json']
        )->assertStatus(404);
    }

    public function test_response_outside_the_audience_is_not_found(): void
    {
        $experiment = Experiment::create($this->payload([
            'targeting' => ['audience' => 'user_ids', 'user_ids' => [999999]],
        ]));

        $this->json(
            'POST',
            self::TEST_URL . '/' . $experiment->id . '/responses',
            ['answers' => ['score' => 4], 'path' => '/en/search'],
            ['Accept' => 'application/json']
        )->assertStatus(404);
    }

    public function test_anonymous_contact_opt_in_requires_an_email(): void
    {
        $experiment = Experiment::create($this->payload());

        $this->json(
            'POST',
            self::TEST_URL . '/' . $experiment->id . '/responses',
            ['answers' => ['score' => 4], 'path' => '/en/search', 'contact' => true, 'email' => 'not-an-email'],
            ['Accept' => 'application/json', 'x-request-session-id' => self::SESSION_ID]
        )->assertStatus(400);
    }

    public function test_response_without_known_answers_is_rejected(): void
    {
        $experiment = Experiment::create($this->payload());

        $this->json(
            'POST',
            self::TEST_URL . '/' . $experiment->id . '/responses',
            ['answers' => ['unknown' => 'y'], 'path' => '/en/search'],
            ['Accept' => 'application/json', 'x-request-session-id' => self::SESSION_ID]
        )->assertStatus(400);

        $this->assertSame(0, $experiment->fresh()->responses_count);
    }

    public function test_create_keeps_the_whole_definition(): void
    {
        $id = $this->json('POST', self::TEST_URL, $this->payload(), $this->header)->json('data');

        $block = Experiment::find($id)->definition['steps'][0]['blocks'][0];
        $this->assertSame('How easy was it?', $block['label']);
        $this->assertSame(5, $block['max']);
    }

    public function test_create_rejects_a_date_that_is_not_iso(): void
    {
        $this->json(
            'POST',
            self::TEST_URL,
            $this->payload(['starts_at' => 'Thu, 12 Nov 2026 00:00:00 GMT']),
            $this->header
        )->assertStatus(400);
    }

    public function test_show_returns_an_experiment(): void
    {
        $experiment = Experiment::create($this->payload());

        $this->json('GET', self::TEST_URL . '/' . $experiment->id, [], $this->header)
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Onboarding layout');
    }

    public function test_active_excludes_an_experiment_that_has_not_started(): void
    {
        $future = Experiment::create($this->payload(['starts_at' => now()->addDay()->toDateString()]));

        $ids = collect($this->json('GET', self::TEST_URL . '/active', [], ['Accept' => 'application/json'])->json('data'))
            ->pluck('id');

        $this->assertNotContains($future->id, $ids);
    }

    public function test_logged_out_audience_is_only_shown_to_signed_out_visitors(): void
    {
        $signedOut = Experiment::create($this->payload(['targeting' => ['audience' => 'logged_out']]));

        $anonymousIds = collect($this->json('GET', self::TEST_URL . '/active', [], ['Accept' => 'application/json'])->json('data'))
            ->pluck('id');
        $meIds = collect($this->json('GET', self::TEST_URL . '/active/me', [], $this->userHeader())->json('data'))
            ->pluck('id');

        $this->assertContains($signedOut->id, $anonymousIds);
        $this->assertNotContains($signedOut->id, $meIds);
    }

    public function test_create_rejects_a_roles_audience(): void
    {
        $this->json(
            'POST',
            self::TEST_URL,
            $this->payload(['targeting' => ['audience' => 'roles', 'roles' => ['hdruk.admin']]]),
            $this->header
        )->assertStatus(400);
    }
}
