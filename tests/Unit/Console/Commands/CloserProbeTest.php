<?php

namespace Tests\Unit\Console\Commands;

use App\Modules\Closer\CloserClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloserProbeTest extends TestCase
{
    private const API = 'closer.example.test/api/v1/';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.closer.base_url' => 'https://closer.example.test',
            'services.closer.username' => 'example-user',
            'services.closer.password' => 'example-pass',
        ]);
        Cache::forget(CloserClient::TOKEN_CACHE_KEY);
        Storage::fake('local');
    }

    private function fakeCloser(array $item, array $set = []): void
    {
        Http::fake([
            'closer.example.test/token/createtoken' => Http::response(['access_token' => 'tok', 'expires' => 3600]),
            self::API.'item/uk.example/abc-123/history' => Http::response([['Version' => 1], ['Version' => 2]]),
            self::API.'item/uk.example/abc-123' => Http::response($item),
            self::API.'set/uk.example/abc-123/2/typed' => Http::response($set),
        ]);
    }

    public function test_a_missing_item_fails_and_saves_nothing(): void
    {
        Http::fake([
            'closer.example.test/token/createtoken' => Http::response(['access_token' => 'tok', 'expires' => 3600]),
            self::API.'*' => Http::response('', 404),
        ]);

        $this->artisan('closer:probe', ['identifier' => 'uk.example/abc-123'])
            ->expectsOutputToContain('item: HTTP 404')
            ->assertFailed();

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_it_saves_raw_responses_and_summarises_children_by_type(): void
    {
        $this->fakeCloser(
            ['ItemType' => 'study-type', 'Version' => 2, 'VersionDate' => '2026-01-01T00:00:00'],
            [
                ['Item1' => ['Identifier' => 'a'], 'Item2' => 'variable-type'],
                ['Item1' => ['Identifier' => 'b'], 'Item2' => 'variable-type'],
                ['Item1' => ['Identifier' => 'c'], 'Item2' => 'question-type'],
            ],
        );

        $this->artisan('closer:probe', ['identifier' => 'urn:ddi:uk.example:abc-123:1'])
            ->expectsOutputToContain('Version: 2')
            ->expectsOutputToContain('VersionDate: 2026-01-01T00:00:00')
            ->expectsOutputToContain('History: 2 versions')
            ->expectsOutputToContain('Children: 3')
            ->expectsOutputToContain('variable-type: 2')
            ->expectsOutputToContain('question-type: 1')
            ->assertSuccessful();

        $disk = Storage::disk('local');
        foreach (['item', 'history', 'set'] as $name) {
            $disk->assertExists("closer-probe/uk.example_abc-123/{$name}.json");
        }
    }

    public function test_an_unrecognised_set_shape_is_reported_rather_than_miscounted(): void
    {
        $this->fakeCloser(['Version' => 2], [['Something' => 'else']]);

        $this->artisan('closer:probe', ['identifier' => 'uk.example/abc-123'])
            ->expectsOutputToContain('Children: 1')
            ->expectsOutputToContain('Unrecognised child shape, keys: Something')
            ->assertSuccessful();
    }
}
