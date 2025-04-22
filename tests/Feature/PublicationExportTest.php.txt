<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Exports\PublicationExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class PublicationExportTest extends TestCase
{
    public function test_generates_excel_publication_download(): void
    {
        Storage::fake('local');

        $testData = $this->returnTestData();

        $export = new PublicationExport($testData);

        $fileName = 'publication-table.csv';
        Excel::store($export, $fileName, 'local');

        Storage::disk('local')->assertExists($fileName);
    }

    public function returnTestData()
    {
        return [
            [
                "_explanation" => [],
                "_id" => "298",
                "_score" => 7.7450438,
                "_source" => [
                    "abstract" => "This project aims to better understand and ultimately prevent potentially life-threatening asthma attacks by moving towards a learning healthcare system, where data gathering is embedded in daily practice. The UK has one of the highest burdens of asthma in the world. Each year there are at least 6 million primary care consultations, 93,000 hospital admissions, and 1400 deaths – the majority of these deaths being preventable. What’s more, the financial cost of asthma to the UK is over £1 billion. Most people with asthma have long periods where they are either asymptomatic or experience only relatively mild symptoms; yet some go on to have asthma attacks, which can prove life-threatening. A key aim, therefore, is to better understand and ultimately prevent the risk of asthma exacerbations. However, the lack of timely patient data and a fragmented approach to asthma care make this a challenge. This project aims to move towards a learning public health system for asthma, where knowledge generation and sharing is embedded in daily practice, aiding continual improvements in patient care. Through this project routine NHS patient data from the Oxford-RCGP RSC network (ORCHID), Optimum Patient Care Research Database (OPCRD), Clinical Practice Research Datalink (CPRD) will be examined. CPRD includes linked hospital, mortality and socio-economic status data for England, and will allow the team to look in detail at how many people with asthma end up in A&E; how many are then hospitalised; and if they had modifiable risk factors which could have been managed in advance. To test if near-real time primary care data could be used for actionable insights for asthma management, a dashboard with asthma epidemiology and modifiable factors will be created for GP practices in ORCHID.",
                    "authors" => "Authors A, B, and C",
                    "datasetTitles" => [],
                    "journalName" => "My Test Journal Name",
                    "publicationDate" => "2022-01-18",
                    "publicationType" => "peer-reviewed",
                    "created_at" => "2022-01-18T15 =>40 =>14.000000Z"
                ],
                "highlight" => [
                    "title" => [
                        "this is a highlight title"
                    ],
                    "abstract" => "This project aims to better understand and ultimately prevent potentially life-threatening asthma attacks.",
                ],
                "team" => [
                    "id" => 103,
                    "pid" => "e49209a7-385e-4a18-b44c-8a80bd228e93",
                    "created_at" => "2024-02-12T17 =>45 =>45.000000Z",
                    "updated_at" => "2024-02-12T17 =>45 =>53.000000Z",
                    "deleted_at" => null,
                    "name" => "OPTIMUM PATIENT CARE",
                    "enabled" => true,
                    "allows_messaging" => false,
                    "workflow_enabled" => false,
                    "access_requests_management" => false,
                    "uses_5_safes" => false,
                    "is_admin" => false,
                    "member_of" => "OTHER",
                    "contact_point" => null,
                    "application_form_updated_by" => "System Generated",
                    "application_form_updated_on" => "0001-01-01 00 =>00 =>00",
                    "mongo_object_id" => "60c08fa84401b6d377682068",
                    "notification_status" => false,
                    "is_question_bank" => false
                ],
                "mongoObjectId" => "61e6df5eb81d5cda54d1918b"
            ]
        ];
    }
}
