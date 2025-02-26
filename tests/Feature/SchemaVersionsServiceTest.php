<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\SchemaVersionsService;

class SchemaVersionsServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_returns_schema_versions_on_successful_api_response()
    {
        $mockResponse = [
            'GWDM_TRASER_IDENT' => "gdmv1",
            'GWDM' => "GWDM",
            'GWDM_CURRENT_VERSION' => "2.0",
            'FORM_HYDRATION_SCHEMA_MODEL' => "HDRUK",
            'FORM_HYDRATION_SCHEMA_LATEST_VERSION' => "3.0.0"
        ];

        Http::fake([
            env("TRASER_SERVICE_URL") . '/latest' => Http::response($mockResponse, 200),
        ]);

        $data = SchemaVersionsService::getSchemaVersions();

        $this->assertNotNull($data);
        $this->assertEquals("HDRUK", $data['FORM_HYDRATION_SCHEMA_MODEL']);
        $this->assertEquals("3.0.0", $data['FORM_HYDRATION_SCHEMA_LATEST_VERSION']);
    }

    /** @test */
    public function it_returns_null_when_api_fails()
    {
        Http::fake([
            env("TRASER_SERVICE_URL") . '/latest' => Http::response(null, 500),
        ]);

        $data = SchemaVersionsService::getSchemaVersions();

        $this->assertNull($data);
    }
}
