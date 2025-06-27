<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Traits\DatasetsV2Helpers;

class ExtractMetadataTest extends TestCase
{
    use DatasetsV2Helpers;

    public function test_it_handles_double_encoded_json()
    {
        $input = '{"metadata":"{\"foo\":\"bar\"}"}';
        $result = $this->extractMetadata($input);

        $this->assertEquals(
            ['metadata' => ['foo' => 'bar']],
            $result
        );
    }

    public function it_handles_metadata_key_with_json_string()
    {
        $result = $this->extractMetadata([
            'metadata' => '{"foo":"bar"}'
        ]);
        $this->assertEquals(['metadata' => ['foo' => 'bar']], $result);
    }


    public function it_handles_nested_metadata_metadata_key()
    {
        $result = $this->extractMetadata([
            'metadata' => [
                'metadata' => ['foo' => 'bar']
            ]
        ]);
        $this->assertEquals(['metadata' => ['foo' => 'bar']], $result);
    }

    public function it_handles_invalid_json_gracefully()
    {
        $invalidJson = '{"foo":"bar"';
        $result = $this->extractMetadata($invalidJson);
        $this->assertEquals($invalidJson, $result);
    }

    public function it_returns_metadata_as_is_if_already_array()
    {
        $input = ['metadata' => ['foo' => 'bar']];
        $result = $this->extractMetadata($input);
        $this->assertEquals($input, $result);
    }
}
