<?php

namespace Tests\Feature;

use App\Services\Gwdm\GwdmHandlerFactory;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * toElasticFields() centralises the GWDM-path extraction for the Elasticsearch
 * dataset index on the version-appropriate handler (previously scattered across
 * IndexElastic as getValueByPossibleKeys(...) calls). It takes a reconstructed
 * envelope and returns only the metadata-derived fields; relationship/DB fields
 * are merged in by the caller.
 */
class GwdmHandlerElasticFieldsTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected function setUp(): void
    {
        $this->commonSetUp();
    }

    private function envelope(): array
    {
        return [
            'gwdmVersion' => '2.0',
            'metadata' => $this->getMetadataV2p0()['metadata'],
        ];
    }

    public function test_extracts_metadata_fields_from_the_envelope(): void
    {
        $fields = (new GwdmHandlerFactory)->resolve('2.0')->toElasticFields($this->envelope());

        $this->assertStringStartsWith('Publications that mention HDR-UK', $fields['abstract']);
        $this->assertSame('HDR UK Papers & Preprints', $fields['shortTitle']);
        $this->assertContains('list of papers', $fields['dataType']);
        $this->assertSame('HEALTH DATA RESEARCH UK', $fields['publisherName']);
        $this->assertSame('TRE', $fields['accessService']);
        $this->assertContains('OTHER', $fields['conformsTo']);
        $this->assertContains('OTHER', $fields['formatAndStandards']);
    }

    public function test_material_types_come_through_as_a_json_list(): void
    {
        $fields = (new GwdmHandlerFactory)->resolve('2.0')->toElasticFields($this->envelope());

        $this->assertTrue($fields['containsBioSamples']);
        $this->assertContains('Blood', $fields['sampleAvailability']);
        $this->assertContains('Urine', $fields['sampleAvailability']);
        $this->assertTrue(array_is_list($fields['sampleAvailability']));
    }

    public function test_missing_fields_fall_back_to_defaults(): void
    {
        // Empty envelope -> every metadata path is absent.
        $fields = (new GwdmHandlerFactory)->resolve('2.0')->toElasticFields([]);

        $this->assertSame('', $fields['abstract']);
        $this->assertSame([], $fields['dataType']);
        $this->assertSame(-1, $fields['populationSize']);
        $this->assertFalse($fields['containsBioSamples']);
        $this->assertNull($fields['sampleAvailability']);
        $this->assertNull($fields['formatAndStandards']);
    }
}
