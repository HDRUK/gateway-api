<?php

namespace Tests\Unit\Modules\Closer;

use App\Modules\Closer\CloserClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CloserClientTest extends TestCase
{
    private const ITEM_URL = 'https://closer.example.test/api/v1/item/example.agency/abc-123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.closer.base_url' => 'https://closer.example.test',
            'services.closer.username' => 'example-user',
            'services.closer.password' => 'example-pass',
        ]);
        Cache::forget(CloserClient::TOKEN_CACHE_KEY);
    }

    public function test_a_token_is_reused_across_calls_rather_than_requested_each_time(): void
    {
        Http::fake([
            'closer.example.test/token/createtoken' => Http::response(['access_token' => 'tok-1', 'expires' => 3600]),
            'closer.example.test/api/v1/*' => Http::response(['ok' => true]),
        ]);

        $client = new CloserClient;
        $client->get('item/example.agency/abc-123');
        $client->get('item/example.agency/abc-123/history');

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $r) => $r->url() === self::ITEM_URL
            && $r->hasHeader('Authorization', 'Bearer tok-1'));
    }

    public function test_a_rejected_token_is_replaced_and_the_call_retried_once(): void
    {
        Http::fake([
            'closer.example.test/token/createtoken' => Http::sequence()
                ->push(['access_token' => 'stale', 'expires' => 3600])
                ->push(['access_token' => 'fresh', 'expires' => 3600]),
            'closer.example.test/api/v1/*' => Http::sequence()
                ->push('', 401)
                ->push(['ok' => true]),
        ]);

        $response = (new CloserClient)->get('item/example.agency/abc-123');

        $this->assertTrue($response->successful());
        Http::assertSent(fn (Request $r) => $r->url() === self::ITEM_URL
            && $r->hasHeader('Authorization', 'Bearer fresh'));
    }

    public function test_a_failed_token_request_throws_without_leaking_credentials(): void
    {
        Http::fake(['closer.example.test/token/createtoken' => Http::response('', 403)]);

        try {
            (new CloserClient)->get('item/example.agency/abc-123');
            $this->fail('Expected an exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('403', $e->getMessage());
            $this->assertStringNotContainsString('example-pass', $e->getMessage());
        }
    }

    #[DataProvider('identifiers')]
    public function test_it_parses_identifiers_in_the_forms_closer_uses(string $input, array $expected): void
    {
        $this->assertSame($expected, CloserClient::parseIdentifier($input));
    }

    public static function identifiers(): array
    {
        return [
            'agency/id' => ['uk.example/abc-123', ['agency' => 'uk.example', 'id' => 'abc-123', 'version' => null]],
            'DDI URN' => ['urn:ddi:uk.example:abc-123:7', ['agency' => 'uk.example', 'id' => 'abc-123', 'version' => 7]],
            'discovery URL' => ['https://discovery.closer.ac.uk/item/uk.example/abc-123', ['agency' => 'uk.example', 'id' => 'abc-123', 'version' => null]],
        ];
    }

    public function test_a_bare_identifier_without_an_agency_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CloserClient::parseIdentifier('abc-123');
    }
}
