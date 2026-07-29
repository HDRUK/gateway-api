<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Http;
use MetadataManagementController as MMC;
use Tests\TestCase;

class ValidateDataModelErrorsTest extends TestCase
{
    public function test_validate_returns_traser_errors_on_failure(): void
    {
        $traserErrorBody = [
            'error' => 'metadata validation failed',
            'details' => [
                ['instancePath' => '/linkage/datasetLinkage', 'message' => 'must be object'],
            ],
            'data' => ['metadata' => []],
        ];

        Http::fake(['*/validate*' => Http::response($traserErrorBody, 400)]);

        $json = json_encode(['metadata' => ['foo' => 'bar']]);
        $errors = MMC::validateDataModelType($json, 'GWDM', '2.0');

        $this->assertIsArray($errors);
        $this->assertSame('metadata validation failed', $errors['error']);
        $this->assertSame('must be object', $errors['details'][0]['message']);
    }

    public function test_validate_returns_null_on_success(): void
    {
        Http::fake(['*/validate*' => Http::response(['details' => 'all ok'], 200)]);

        $json = json_encode(['metadata' => ['foo' => 'bar']]);

        $this->assertNull(MMC::validateDataModelType($json, 'GWDM', '2.0'));
    }
}
