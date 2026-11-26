<?php

namespace Tests\Unit\Console\Commands;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloserAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.closer.base_url' => 'https://closer.example.test',
            'services.closer.username' => 'example-user',
            'services.closer.password' => 'example-pass',
        ]);
    }

    public function test_it_fails_without_calling_closer_when_credentials_are_missing(): void
    {
        config(['services.closer.password' => null]);
        Http::fake();

        $this->artisan('app:closer-auth')
            ->expectsOutputToContain('CLOSER_API_USERNAME and CLOSER_API_PASSWORD must be set')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_a_refused_login_reports_where_the_refusal_came_from(): void
    {
        Http::fake([
            'closer.example.test/*' => Http::response('', 403, ['server' => 'cloudflare']),
        ]);

        $this->artisan('app:closer-auth')
            ->expectsOutputToContain('Status: 403')
            ->expectsOutputToContain('cf-mitigated: -')
            ->assertFailed();
    }

    public function test_it_requests_a_token_from_the_site_root_not_the_api_prefix(): void
    {
        Http::fake([
            'closer.example.test/token/createtoken' => Http::response(['access_token' => 'secret-token-value']),
        ]);

        $this->artisan('app:closer-auth')
            ->expectsOutputToContain('Keys: access_token')
            ->doesntExpectOutputToContain('secret-token-value')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'https://closer.example.test/token/createtoken'
            && $request['username'] === 'example-user'
            && $request['password'] === 'example-pass'
            && $request->hasHeader('Accept', 'application/json'));
    }
}
