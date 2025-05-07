<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use CloudLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TestController extends Controller
{
    public function __construct()
    {
    }

    /**
     *
     * Test with Jwt credentials
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function test(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Lorem ipsum dolor sit amet, consectetur adip',
            'request_method' => $request->method(),
            'request_body' => $request->all(),
        ]);
    }

    public function testCheckAccess(Request $request)
    {
        return response()->json([
            'message' => 'TestController.checkRoles',
            'request_method' => $request->method(),
            'request_body' => $request->all(),
        ]);
    }

    public function testPubSubService(Request $request): JsonResponse
    {
        Auditor::log([
            'user_id' => 1,
            'action_type' => 'CREATE',
            'action_name' => 'action service test',
            'description' => 'description test',
        ]);

        return response()->json(['status' => 'success']);
    }

    public function testGCPLogger(Request $request): JsonResponse
    {
        CloudLogger::write([
            'type' => 'send array',
            'user_id' => 1,
            'action_type' => 'CREATE',
            'action_name' => 'action service test',
            'description' => 'description test',
        ]);
        CloudLogger::write('send string');

        return response()->json(['status' => 'success']);
    }

    public function testListDatasets(Request $request): JsonResponse
    {
        return response()->json([
            "total" => 3,
            "items" => [
                [
                    "persistentId" => "242c84be-a866-ac0c-0000-000000001bbd",
                    "name" => "Kent Integrated Dataset - GP Consultations",
                    "description" => "Primary Care Consultations information, recording the intereactions that patients with medical staff at GP practices",
                    "version" => "1.0.0",
                    "self" => "https://nhse-ics-kms-uat5-ui.metadata.works/api/federation/dataset/7101",
                    "source" => "",
                    "@schema" => "https://hdruk.github.io/schemata/schema/dataset/2.1.0/dataset.schema.json",
                    "issued" => "2024-03-09T23:22:05+00:00",
                    "modified" => "2024-07-03T09:54:34+00:00"
                ],
                [
                    "persistentId" => "242c84be-a866-ac0c-0000-000000001bbe",
                    "name" => "Kent Integrated Dataset - GP Event",
                    "description" => "Primary Care Event information, recording the intereactions that patients with medical staff at GP practices and some read-coded numeric data",
                    "version" => "1.0.0",
                    "self" => "https://nhse-ics-kms-uat5-ui.metadata.works/api/federation/dataset/7102",
                    "source" => "",
                    "@schema" => "https://hdruk.github.io/schemata/schema/dataset/2.1.0/dataset.schema.json",
                    "issued" => "2024-03-09T23:22:07+00:00",
                    "modified" => "2024-07-03T09:55:18+00:00"
                ],
                [
                    "persistentId" => "242c84be-a866-ac0c-0000-000000002564",
                    "name" => "Sample Dataset",
                    "version" => "1.0.0",
                    "self" => "https://nhse-ics-kms-uat5-ui.metadata.works/api/federation/dataset/9572",
                    "source" => "",
                    "@schema" => "https://hdruk.github.io/schemata/schema/dataset/2.1.0/dataset.schema.json",
                    "issued" => "2024-01-29T17:01:20+00:00",
                    "modified" => "2024-11-26T09:37:22+00:00"
                ]
            ]
        ]);
    }

    public function testListDatasetId(Request $request, $id): JsonResponse
    {
        if ($id === '242c84be-a866-ac0c-0000-000000001bbd') {
            return response()->json($this->testOne());
        }

        if ($id === '242c84be-a866-ac0c-0000-000000001bbe') {
            return response()->json($this->testTwo());
        }

        if ($id === '242c84be-a866-ac0c-0000-000000002564') {
            return response()->json($this->testTwo());
        }
    }

    public function testThree()
    {
        return [
            "identifier" => "242c84be-a866-ac0c-0000-000000000002",
            "version" => "1.0.0",
            "revisions" => [
                [
                    "version" => "1.0.0",
                    "url" => "https://nhse-ics-kms-uat5-ui.metadata.works/browser/dataset?id=9572"
                ]
            ],
            "issued" => "2024-01-29T17:01:20+00:00",
            "modified" => "2024-11-26T09:37:22+00:00",
            "summary" => [
                "title" => "Sample Dataset",
                "abstract" => "Lorem ipsum dolor sit amet.",
                "publisher" => [
                    "name" => "Lorem",
                    "contactPoint" => [
                        "test@test.com"
                    ],
                    "memberOf" => "OTHER"
                ],
                "contactPoint" => "jonny@metadataworks.co.uk",
                "keywords" => ["Lorem","Ipsum"]
            ],
            "accessibility" => [
                    "access" => [
                    "accessRights" => [],
                    "jurisdiction" => [
                        "GB-ENG"
                    ],
                    "dataController" => "  "
                ]
            ],
            "observations" => []
        ];
    }

    public function testTwo()
    {
        return [
            "identifier" => "242c84be-a866-ac0c-0000-000000001bbe",
            "version" => "1.0.0",
            "revisions" => [
                [
                    "version" => "1.0.0",
                    "url" => "https://nhse-ics-kms-uat5-ui.metadata.works/browser/dataset?id=7102"
                ]
            ],
            "issued" => "2024-03-09T23:22:07+00:00",
            "modified" => "2024-07-03T09:55:18+00:00",
            "summary" => [
                "title" => "Kent Integrated Dataset - GP Event",
                "abstract" => "Primary Care Event information, recording the intereactions that patients with medical staff at GP practices and some read-coded numeric data",
                "publisher" => [
                    "name" => "Kent & Medway Data Warehouse",
                    "logo" => "https://www.kentandmedway.icb.nhs.uk/application/files/cache/thumbnails/b9a92ca7b10d21ae1cdf056d95e99659.png",
                    "description" => "https://www.kentandmedway.icb.nhs.uk/about-us",
                    "contactPoint" => [
                        "kmicb.bi@nhs.net"
                    ],
                    "memberOf" => "OTHER"
                ],
                "contactPoint" => "kmicb.bi@nhs.net",
                "keywords" => ["General practitioner", "Event", "GP   ", "HDRUK", "Primary care"],
                "doiName" => "10.81022/wfv3-tz28"
            ],
            "documentation" => [
                "description" => "Primary Care Event information, recording the intereactions that patients with medical staff at GP practices and some read-coded numeric data",
                "isPartOf" => ["Primary Care Data"]
            ],
            "coverage" => [
                "spatial" => "Kent and Medway",
                "typicalAgeRange" => "0-120",
                "physicalSampleAvailability" => ["NOT AVAILABLE"],
                "followup" => "UNKNOWN"
            ],
            "provenance" => [
                "origin" => [
                    "purpose" => ["CARE"],
                    "source" => ["EPR"],
                    "collectionSituation" => ["PRIMARY CARE"]
                ],
                "temporal" => [
                    "accrualPeriodicity" => "STATIC",
                    "distributionReleaseDate" => "2019-06-24",
                    "startDate" => "1901-01-01",
                    "endDate" => "2019-06-24",
                    "timeLag" => "NOT APPLICABLE"
                ]
            ],
            "accessibility" => [
                "usage" => [
                    "dataUseLimitation" => ["RESEARCH SPECIFIC RESTRICTIONS"],
                    "dataUseRequirements" => [
                        "ETHICS APPROVAL REQUIRED",
                        "GEOGRAPHICAL RESTRICTIONS",
                        "NOT FOR PROFIT USE",
                        "PROJECT SPECIFIC RESTRICTIONS",
                        "TIME LIMIT ON USE",
                        "USER SPECIFIC RESTRICTION"
                    ],
                    "resourceCreator" => ["Kent and Mewday ICB", "Kent and Medway Data Warehouse"]
                ],
                "access" => [
                    "accessRights" => [],
                    "accessService" => "  ",
                    "accessRequestCost" => "To be agreed per project by SDE program",
                    "deliveryLeadTime" => "1-2 MONTHS",
                    "jurisdiction" => ["GB-ENG"],
                    "dataController" => "Kent and Medway ICB",
                    "dataProcessor" => "Kent and Medway Data Warehouse"
                ],
                "formatAndStandards" => [
                    "vocabularyEncodingScheme" => [
                        "READ",
                        "NHS NATIONAL CODES",
                        "ODS"
                    ],
                    "conformsTo" => [
                        "LOCAL"
                    ],
                    "language" => [
                        "en"
                    ],
                    "format" => [
                        "Text"
                    ]
                ]
            ],
            "observations" => [
                [
                    "observedNode" => "PERSONS",
                    "measuredValue" => 1756178,
                    "disambiguatingDescription" => "     ",
                    "observationDate" => "2019-06-24",
                    "measuredProperty" => "COUNT"
                ],
                [
                    "observedNode" => "EVENTS",
                    "measuredValue" => 732236154,
                    "disambiguatingDescription" => "     ",
                    "observationDate" => "2019-06-24",
                    "measuredProperty" => "COUNT"
                ]
            ],
            "structuralMetadata" => [
                [
                    "name" => "GP_Event",
                    "description" => "Primary Care Event Table",
                    "elements" => [
                        [
                            "name" => "Unique_ID",
                            "dataType" => "String",
                            "description" => "Unique reference to the record",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Commissioner_Code",
                            "dataType" => "String",
                            "description" => "Commissioner for the event",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Practice_Code",
                            "dataType" => "String",
                            "description" => "Code for GP practice that is as at the activity date",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Age  ",
                            "dataType" => "Integer",
                            "description" => "Age of patient at activity date",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Sex  ",
                            "dataType" => "String",
                            "description" => "Sex of patient at activity date",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Event_Read_Code",
                            "dataType" => "String",
                            "description" => "Read code of the event",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Num_Result",
                            "dataType" => "floating point",
                            "description" => "Numeric result of the event",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Num_Result_2",
                            "dataType" => "floating point",
                            "description" => "Second numeric result of the event",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Activity_Date",
                            "dataType" => "date",
                            "description" => "Date of the event activity",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Pseudonymised_NHS_Number",
                            "dataType" => "String",
                            "description" => "NHS number of patient, encrypted with 256 bit SHA with salt",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Unique_Consultation_ID",
                            "dataType" => "String",
                            "description" => "Unique reference to the consultation record",
                            "sensitive" => false
                        ],
                        [
                            "name" => "SNOMED_Concept",
                            "dataType" => "bigint",
                            "description" => "SNOMED concept of Event (https://www.datadictionary.nhs.uk/data_elements/snomed_ct_code.html)",
                            "sensitive" => false
                        ],
                        [
                            "name" => "SNOMED_Description",
                            "dataType" => "bigint",
                            "description" => "SNOMED description of Event",
                            "sensitive" => false
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testOne()
    {
        return [
            "identifier" => "242c84be-a866-ac0c-0000-000000001bbd",
            "version" => "1.0.0",
            "revisions" => [
                [
                    "version" => "1.0.0",
                    "url" => "https://nhse-ics-kms-uat5-ui.metadata.works/browser/dataset?id=7101"
                ]
            ],
            "issued" => "2024-03-09T23:22:05+00:00",
            "modified" => "2024-07-03T09:54:34+00:00",
            "summary" => [
                "title" => "Kent Integrated Dataset - GP Consultations",
                "abstract" => "Primary Care Consultations information, recording the intereactions that patients with medical staff at GP practices",
                "publisher" => [
                    "name" => "Kent & Medway Data Warehouse",
                    "logo" => "https://www.kentandmedway.icb.nhs.uk/application/files/cache/thumbnails/b9a92ca7b10d21ae1cdf056d95e99659.png",
                    "description" => "https://www.kentandmedway.icb.nhs.uk/about-us",
                    "contactPoint" => [
                        "kmicb.bi@nhs.net"
                    ],
                    "memberOf" => "OTHER"
                ],
                "contactPoint" => "kmicb.bi@nhs.net",
                "keywords" => [
                    "General practitioner",
                    "Consultation",
                    "GP   ",
                    "Primary care"
                ],
                "doiName" => "10.81022/kfqp-wq04"
            ],
            "documentation" => [
                "description" => "Primary Care Consultations information, recording the intereactions that patients with medical staff at GP practices",
                "isPartOf" => [
                    "Primary Care Data"
                ]
            ],
            "coverage" => [
                "spatial" => "Kent and Medway",
                "typicalAgeRange" => "0-120",
                "physicalSampleAvailability" => [
                    "NOT AVAILABLE"
                ],
                "followup" => "UNKNOWN"
            ],
            "provenance" => [
                "origin" => [
                    "purpose" => [
                        "CARE"
                    ],
                    "source" => [
                        "EPR"
                    ],
                    "collectionSituation" => [
                        "PRIMARY CARE"
                    ]
                ],
                "temporal" => [
                    "accrualPeriodicity" => "STATIC",
                    "distributionReleaseDate" => "2019-06-24",
                    "startDate" => "2014-04-01",
                    "endDate" => "2019-06-24",
                    "timeLag" => "NOT APPLICABLE"
                ]
            ],
            "accessibility" => [
                "usage" => [
                    "dataUseLimitation" => [
                        "RESEARCH SPECIFIC RESTRICTIONS"
                    ],
                    "dataUseRequirements" => [
                        "ETHICS APPROVAL REQUIRED",
                        "GEOGRAPHICAL RESTRICTIONS",
                        "NOT FOR PROFIT USE",
                        "PROJECT SPECIFIC RESTRICTIONS",
                        "TIME LIMIT ON USE",
                        "USER SPECIFIC RESTRICTION"
                    ],
                    "resourceCreator" => [
                        "Kent and Mewday ICB",
                        "Kent and Medway Data Warehouse"
                    ]
                ],
                "access" => [
                    "accessRights" => [],
                    "accessService" => "  ",
                    "accessRequestCost" => "To be agreed per project by SDE program",
                    "deliveryLeadTime" => "1-2 MONTHS",
                    "jurisdiction" => [
                        "GB-ENG"
                    ],
                    "dataController" => "Kent and Medway ICB",
                    "dataProcessor" => "Kent and Medway Data Warehouse"
                ],
                "formatAndStandards" => [
                    "vocabularyEncodingScheme" => [
                        "READ",
                        "NHS NATIONAL CODES",
                        "ODS"
                    ],
                    "conformsTo" => [
                        "LOCAL"
                    ],
                    "language" => [
                        "en"
                    ],
                    "format" => [
                        "Text"
                    ]
                ]
            ],
            "observations" => [
                [
                    "observedNode" => "PERSONS",
                    "measuredValue" => 1350436,
                    "disambiguatingDescription" => "     ",
                    "observationDate" => "2019-06-24",
                    "measuredProperty" => "COUNT"
                ],
                [
                    "observedNode" => "EVENTS",
                    "measuredValue" => 29514552,
                    "disambiguatingDescription" => "     ",
                    "observationDate" => "2019-06-24",
                    "measuredProperty" => "COUNT"
                ]
            ],
            "structuralMetadata" => [
                [
                    "name" => "GP Consultations",
                    "description" => "Primary Care Consultations Table",
                    "elements" => [
                        [
                            "name" => "Commissioner_Code",
                            "dataType" => "String",
                            "description" => "Commissioner for the consultation",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Activity_Date",
                            "dataType" => "date",
                            "description" => "Date of the activity",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Data_Currency",
                            "dataType" => "String",
                            "description" => "Consultant or home visit",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Current_Practice_Code",
                            "dataType" => "String",
                            "description" => "Code for GP practice that is current for the patient from PMI",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Practice_Code",
                            "dataType" => "String",
                            "description" => "Code for GP practice that is as at the activity date",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Consultation_Read_Code",
                            "dataType" => "String",
                            "description" => "Read code of the consultation",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Sex  ",
                            "dataType" => "String",
                            "description" => "Sex of patient at activity date",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Unique_ID",
                            "dataType" => "String",
                            "description" => "Unique reference to the consultation record",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Pseudonymised_NHS_Number",
                            "dataType" => "String",
                            "description" => "NHS number of patient, encrypted with 256 bit SHA with salt",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Staff_Member_Type",
                            "dataType" => "String",
                            "description" => "Type of staff member who attended patient",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Pseudonymised_Staff_Member",
                            "dataType" => "String",
                            "description" => "ID of staff member, encrypted with 256 bit SHA with salt",
                            "sensitive" => false
                        ],
                        [
                            "name" => "Pseudonymised_Registration_Number",
                            "dataType" => "String",
                            "description" => "Regstration number of staff member, encrypted with 256 bit SHA with salt",
                            "sensitive" => false
                        ]
                    ]
                ]
            ]
        ];
    }
}
