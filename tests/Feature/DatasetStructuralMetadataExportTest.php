<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Illuminate\Support\Arr;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use App\Exports\DatasetStructuralMetadataExport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatasetStructuralMetadataExportTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $testMetadata;

    public function setUp(): void
    {
        $this->testMetadata = json_decode($this->getMetadata(), true);
    }

    public function test_generates_excel_dataset_structural_metadata_download_type_table(): void
    {
        Storage::fake('local');
        var_dump($this->testMetadata);
        exit();

        $export = Arr::has($this->testMetadata, 'versions.0.metadata.metadata.structuralMetadata') ? 
            $this->testMetadata['versions'][0]['metadata']['metadata']['structuralMetadata'] : 
            [];

        $export = new DatasetStructuralMetadataExport($export);

        $fileName = 'dataset-structural-metadata.csv';
        Excel::store($export, $fileName, 'local');

        Storage::disk('local')->assertExists($fileName);
    }
}
