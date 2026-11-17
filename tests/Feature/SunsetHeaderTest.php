<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class SunsetHeaderTest extends TestCase
{
    use MockExternalApis;

    public function test_a_deprecated_v1_route_without_a_replacement_sends_valid_deprecation_and_sunset_headers(): void
    {
        $response = $this->json('GET', '/api/v1/tools');

        $response->assertStatus(200);
        $response->assertHeader('Deprecation', '@1753228800');
        $response->assertHeader('Sunset', 'Thu, 22 Jan 2026 23:59:59 GMT');
        $response->assertHeaderMissing('Link');
    }

    public function test_every_sunset_route_declares_a_valid_deprecation_date_before_its_sunset_date(): void
    {
        $sunsetRoutes = collect(Route::getRoutes()->getRoutes())
            ->flatMap(fn ($route) => array_map(
                fn (string $middleware) => [$route->uri(), $middleware],
                array_filter($route->middleware(), fn ($m) => is_string($m) && str_starts_with($m, 'sunset'))
            ));

        $this->assertNotEmpty($sunsetRoutes);

        foreach ($sunsetRoutes as [$uri, $middleware]) {
            $params = explode(',', substr($middleware, strlen('sunset:')));
            $this->assertMatchesRegularExpression('/^sunset:/', $middleware, "{$uri} uses sunset without dates");
            $this->assertContains(count($params), [2, 3], "{$uri}: expected deprecatedOn,sunsetOn[,successor]");

            [$deprecatedOn, $sunsetOn] = array_map(
                fn (string $date) => CarbonImmutable::createFromFormat('!Y-m-d', $date, 'UTC'),
                [$params[0], $params[1]]
            );
            $this->assertSame($params[0], $deprecatedOn?->format('Y-m-d'), "{$uri}: invalid deprecation date");
            $this->assertSame($params[1], $sunsetOn?->format('Y-m-d'), "{$uri}: invalid sunset date");
            $this->assertTrue($deprecatedOn < $sunsetOn, "{$uri}: deprecated on or after its sunset");
        }
    }
}
