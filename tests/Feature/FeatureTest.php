<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Pennant\Feature;
use Tests\TestCase;
use Tests\Traits\Authorization;

class FeatureTest extends TestCase
{
    use Authorization;

    public const TEST_URL = '/api/v1/features';

    private array $header = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->authorisationUser(true);
        $jwt = $this->getAuthorisationJwt(true);

        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$jwt,
        ];
    }

    public function test_the_application_return_all_features(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_the_application_cannot_toggle_feature_by_feature_id(): void
    {
        $response = $this->json(
            'PUT',
            self::TEST_URL.'/999999',
            [],
            $this->header
        );

        $response->assertStatus(404);
    }

    public function test_the_application_toggles_features_by_name(): void
    {
        $name = fake()->unique()->slug(2);

        Feature::for(null)->activate($name);
        Feature::flushCache();

        $this->assertTrue(Feature::for(null)->active($name));

        $response = $this->json(
            'PUT',
            self::TEST_URL."/{$name}",
            [],
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertFalse(Feature::for(null)->active($name));

        $response = $this->json(
            'PUT',
            self::TEST_URL."/{$name}",
            [],
            $this->header
        );

        $response->assertStatus(200);
        $this->assertTrue(Feature::for(null)->active($name));
    }

    public function test_toggling_a_feature_posts_a_slack_notification(): void
    {
        config(['services.slack.feature_flag_webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST']);
        config(['app.env' => 'testing']);
        config(['gateway.gateway_url' => 'https://example.test']);

        Http::fake([
            'hooks.slack.com/*' => Http::response(['ok' => true], 200),
        ]);

        $name = fake()->unique()->slug(2);

        Feature::for(null)->deactivate($name);
        Feature::flushCache();

        $response = $this->json(
            'PUT',
            self::TEST_URL."/{$name}",
            [],
            $this->header
        );

        $response->assertStatus(200);

        Http::assertSent(function ($request) use ($name) {
            return $request->url() === 'https://hooks.slack.com/services/TEST/TEST/TEST'
                && str_contains($request['text'], 'Product: Gateway')
                && str_contains($request['text'], "Feature Flag: {$name}")
                && str_contains($request['text'], '🟢')
                && str_contains($request['text'], 'State: 🟢 ON')
                && str_contains($request['text'], 'Toggled by: John Doe')
                && str_contains($request['text'], 'Environment: testing')
                && str_contains($request['text'], 'When: '.now()->format('l jS M Y'))
                && str_contains($request['text'], 'Link: https://example.test/en/account/profile/search-admin?tab=features');
        });
    }

    public function test_toggling_a_feature_skips_slack_notification_when_webhook_not_configured(): void
    {
        config(['services.slack.feature_flag_webhook_url' => '']);

        Http::fake();

        $name = fake()->unique()->slug(2);

        Feature::for(null)->deactivate($name);
        Feature::flushCache();

        $response = $this->json(
            'PUT',
            self::TEST_URL."/{$name}",
            [],
            $this->header
        );

        $response->assertStatus(200);

        Http::assertNothingSent();
    }

    public function test_toggling_a_feature_still_succeeds_if_slack_notification_fails(): void
    {
        config(['services.slack.feature_flag_webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST']);

        Http::fake([
            'hooks.slack.com/*' => Http::response('unavailable', 500),
        ]);

        $name = fake()->unique()->slug(2);

        Feature::for(null)->deactivate($name);
        Feature::flushCache();

        $response = $this->json(
            'PUT',
            self::TEST_URL."/{$name}",
            [],
            $this->header
        );

        $response->assertStatus(200);
        $this->assertTrue(Feature::for(null)->active($name));
    }

    public function test_can_toggle_feature_for_user_without_changing_global(): void
    {
        $this->authorisationUser(true);
        $adminJwt = $this->getAuthorisationJwt(true);
        $adminHeader = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$adminJwt,
        ];

        $this->authorisationUser(false);
        $userJwt = $this->getAuthorisationJwt(false);
        $userHeader = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$userJwt,
        ];

        $name = fake()->unique()->slug(2);

        Feature::for(null)->deactivate($name);
        Feature::flushCache();

        $user = User::find($this->getUserFromJwt($userJwt)['id']);

        $this->assertFalse(Feature::for(null)->active($name));
        $this->assertFalse(Feature::for($user)->active($name));

        $response = $this->json(
            'PUT',
            self::TEST_URL."/users/{$user->id}/{$name}",
            [],
            $adminHeader
        );

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $this->assertFalse(Feature::for(null)->active($name));
        $this->assertTrue(Feature::for($user)->active($name));

        $response = $this->json(
            'GET',
            self::TEST_URL."/users/{$user->id}",
            [],
            $adminHeader
        );

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [$name]]);
        $this->assertTrue($response->decodeResponseJson()['data'][$name]);
    }
}
