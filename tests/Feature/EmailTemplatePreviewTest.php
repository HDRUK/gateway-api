<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class EmailTemplatePreviewTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/emailtemplates/preview';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_preview_renders_body_with_dummy_data_substitution(): void
    {
        Http::fake([
            config('services.mjml.render_url') => function ($request) {
                return Http::response(['html' => '<html>' . $request['mjml'] . '</html>'], 200);
            },
        ]);

        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['body' => '<mjml><mj-body>Hello [[USER_FIRSTNAME]]</mj-body></mjml>'],
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => ['html'],
        ]);

        $content = $response->decodeResponseJson();
        $this->assertStringContainsString('Hello Jane', $content['data']['html']);
        $this->assertStringNotContainsString('[[USER_FIRSTNAME]]', $content['data']['html']);

        // The SanitizeMiddleware htmlentities-encodes all string input; the controller
        // must html_entity_decode it back before sending to the MJML service, or every
        // preview receives '&lt;mjml&gt;...' instead of real markup and fails to render.
        $this->assertStringContainsString('<mjml><mj-body>', $content['data']['html']);
        $this->assertStringNotContainsString('&lt;mjml&gt;', $content['data']['html']);
    }

    public function test_preview_requires_body(): void
    {
        $response = $this->json('POST', self::TEST_URL, [], $this->header);

        $response->assertStatus(400);
    }

    public function test_preview_fails_when_mjml_service_errors(): void
    {
        Http::fake([
            config('services.mjml.render_url') => Http::response('bad gateway', 502),
        ]);

        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['body' => '<mjml><mj-body>test</mj-body></mjml>'],
            $this->header
        );

        $response->assertStatus(500);
    }
}
