<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Exports\ToolListExport;
use Tests\Traits\MockExternalApis;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ToolExportTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_generates_excel_dataset_download_type_list(): void
    {
        Storage::fake('local');

        $testData = $this->returnTestData();

        $export = new ToolListExport($testData);

        $fileName = 'tools-list.csv';
        Excel::store($export, $fileName, 'local');

        Storage::disk('local')->assertExists($fileName);
    }

    public function returnTestData()
    {
        return [
            [
                "_explanation" => [],
                "_id" => "508",
                "_score" => 21.148464,
                "_source" => [
                    "description" => "Application to identify patients with diagnosis of asthma. \n\nBoth past and present symptom.\n\nOutput values Positive.",
                    "name" => "CRIS NLP Asthma",
                    "tags" => [],
                    "programmingLanguage" => "sem-EUR - GATE",
                    "category" => "NLM System",
                    "created_at" => "2024-02-13T12 =>26 =>20.000000Z"
                ],
                "highlight" => [
                    "description" => [
                        "Application to identify patients with diagnosis of <em>asthma<\/em>,"
                    ],
                    "name" => [
                        "CRIS NLP <em>Asthma<\/em>"
                    ]
                ],
                "uploader" => "HDR UK Test Analytics",
                "team" => "",
                "type_category" => [],
                "license" => "Available upon request",
                "programming_language" => [
                    "Not applicable"
                ],
                "programming_package" => [
                    "Natural Language Processing"
                ],
                "datasets" => []
            ]
        ];
    }
}
