<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Arr;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\SpatialCoverageSeeder;
use App\Exports\DatasetStructuralMetadataExport;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class DatasetStructuralMetadataExportTest extends TestCase
{
    use FastRefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $testMetadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            SpatialCoverageSeeder::class,
        ]);

        $this->testMetadata = $this->getMetadata();
    }

    public function test_generates_excel_dataset_structural_metadata_download(): void
    {
        Storage::fake('local');

        $data = Arr::has($this->testMetadata, 'metadata.structuralMetadata') ?
            $this->testMetadata['metadata']['structuralMetadata'] :
            [];

        $export = new DatasetStructuralMetadataExport($data);

        $fileName = 'dataset-structural-metadata.csv';
        Excel::store($export, $fileName, 'local');

        Storage::disk('local')->assertExists($fileName);
    }
}
