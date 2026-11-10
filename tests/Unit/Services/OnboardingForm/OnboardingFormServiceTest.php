<?php

namespace Tests\Unit\Services\OnboardingForm;

use App\Services\OnboardingForm\OnboardingFormService;
use Illuminate\Support\Facades\Cache;
use MetadataManagementController as MMC;
use Tests\TestCase;

class OnboardingFormServiceTest extends TestCase
{
    private const KEY = 'onboarding.schema.HDRUK.4.1.0';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_schema_for_returns_the_cached_schema(): void
    {
        Cache::put(self::KEY, ['title' => 'cached'], 3600);
        MMC::shouldReceive('getSchema')->never();

        $this->assertSame(['title' => 'cached'], app(OnboardingFormService::class)->schemaFor('HDRUK', '4.1.0'));
    }

    public function test_schema_for_fetches_and_caches_the_schema_on_a_miss(): void
    {
        MMC::shouldReceive('getSchema')->once()->with('HDRUK', '4.1.0')->andReturn(['schema' => ['title' => 'fetched']]);

        $this->assertSame(['title' => 'fetched'], app(OnboardingFormService::class)->schemaFor('HDRUK', '4.1.0'));

        $this->travel(3599)->seconds();
        $this->assertSame(['title' => 'fetched'], Cache::get(self::KEY));

        $this->travel(2)->seconds();
        $this->assertNull(Cache::get(self::KEY));
    }

    public function test_schema_for_returns_an_empty_schema_without_caching_it(): void
    {
        MMC::shouldReceive('getSchema')->once()->andReturn(['name' => 'HDRUK']);

        $this->assertSame([], app(OnboardingFormService::class)->schemaFor('HDRUK', '4.1.0'));
        $this->assertFalse(Cache::has(self::KEY));
    }
}
