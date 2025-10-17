<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use App\Exports\DatasetListExport;
use App\Exports\DatasetTableExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class DatasetExportTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_generates_excel_dataset_download_type_table(): void
    {
        Storage::fake('local');

        $testData = $this->returnTestData();

        $export = new DatasetTableExport($testData);

        $expectedArray = [
            "title" => "National Joint Registry - Primary Ankle Replacement dataset",
            "populationSize" => 0,
            "dateRange" => "2010",
            "accessService" => null,
            "dataStandard" => "LOCAL",
            "publisher" => "HQIP",
            "cohortDiscovery" => false,
            "structuralMetadata" => '',
        ];

        $this->assertEquals($export->collection()[0], $expectedArray);

        $fileName = 'datasets-table.csv';
        Excel::store($export, $fileName, 'local');

        Storage::disk('local')->assertExists($fileName);
    }

    public function test_generates_excel_dataset_download_type_list(): void
    {
        Storage::fake('local');

        $testData = $this->returnTestData();

        $export = new DatasetListExport($testData);

        $expectedArray = [
            "title" => "National Joint Registry - Primary Ankle Replacement dataset",
            "abstract" => "The NJR datasets collect continuous, prospective data on patients undergoing primary and revision total joint replacement (hip, knee, shoulder, elbow and ankle). The dataset covers the period 2003 to present and covers the UK and beyond",
            "populationSize" => 0,
            "dateRange" => "2010",
            "accessService" => null,
            "dataStandard" => "LOCAL",
            "publisher" => "HQIP",
            "datasetType" => "Healthdata",
        ];

        $this->assertEquals($export->collection()[0], $expectedArray);

        $fileName = 'datasets-list.csv';
        Excel::store($export, $fileName, 'local');

        Storage::disk('local')->assertExists($fileName);
    }

    public function returnTestData()
    {
        return [
            [
                "_explanation" => [],
                "_id" => "508",
                "_score" => 7.148464,
                "_source" => [
                    "abstract" => "Nationaly defined dataset which ontaining administrative details for inpatient admissions (elective, emergency and maternity) and good coverage of clinical coding of diagnosis (ICD10) and procedures (OPCS4). Includes home birth and delivery spells.",
                    "collections" => [],
                    "conformsTo" => [
                        ""
                    ],
                    "description" => "Nationaly defined dataset which ontaining administrative details for inpatient admissions (elective, emergency and maternity) and good coverage of clinical coding of diagnosis (ICD10) and procedures (OPCS4). Includes home birth and delivery spells.",
                    "endDate" => null,
                    "hasTechnicalMetadata" => true,
                    "keywords" => "BartsHealth",
                    "named_entities" => [
                        "Accesses, Facility",
                        "Active",
                        "Activities",
                        "Adhesive Precoated Cement",
                        "Adjustment Action",
                        "Administrative action",
                        "Age",
                        "Age of Onset",
                        "Agreement",
                        "Anatomic Site",
                        "Ants",
                        "Assessed",
                        "Birth",
                        "Birth Weight",
                        "British Health Service, National",
                        "C0681784",
                        "C1254360",
                        "C1550000",
                        "C1610166",
                        "C3694575",
                        "C3826248",
                        "CDAI - Crohn's disease activity index",
                        "COPD Assessment Test Questionnaire",
                        "Carer (occupation)",
                        "Class",
                        "Classification",
                        "Clinical",
                        "Coitus",
                        "DNA Transposable Elements",
                        "Data Set",
                        "Definition",
                        "Diagnosis",
                        "Diet",
                        "Duration (temporal concept)",
                        "Emergency Situation",
                        "Entry (data)",
                        "Finding",
                        "Genetic Identities",
                        "Geographic Locations",
                        "Guanosine Monophosphate",
                        "Health Service, National",
                        "Home environment",
                        "Hospital admission",
                        "Hospitals",
                        "Indication of (contextual qualifier)",
                        "Individual Adjustment",
                        "Intellectual Product",
                        "Legal",
                        "Levels (qualifier value)",
                        "Live - Specimen Condition",
                        "Local",
                        "Location",
                        "Manufactured Object",
                        "Marital Status",
                        "Measurement",
                        "Methamphetamine",
                        "Methods",
                        "Methods aspects",
                        "Nerve cell and nerve fiber function",
                        "Night time",
                        "Occupations",
                        "Onset of (contextual qualifier)",
                        "Order (taxonomic)",
                        "Oropharyngeal Carcinoma",
                        "Patient Discharge",
                        "Patient Visit",
                        "Patient education (procedure)",
                        "Patient postal code",
                        "Patient referral",
                        "Patients",
                        "Period (temporal qualifier)",
                        "Physical activity",
                        "Population Group",
                        "Protocols documentation",
                        "Reason and justification",
                        "Records",
                        "Refused",
                        "Removed",
                        "Request - action",
                        "Research Activities",
                        "Rett Syndrome",
                        "Security - service",
                        "Sense of identity (observable entity)",
                        "Services",
                        "Social status",
                        "Status",
                        "Supportive assistance",
                        "System",
                        "Techniques",
                        "Tends to be disorganised",
                        "Time",
                        "Title",
                        "Treatment intent",
                        "Update",
                        "Value type - Date",
                        "analytical method",
                        "birth home",
                        "census",
                        "coverage insurance",
                        "day",
                        "delivery system",
                        "diagnostic procedure",
                        "geriatric patient",
                        "health care service",
                        "hospital length of stay",
                        "incident",
                        "month",
                        "parent",
                        "patient care",
                        "patient status",
                        "receptor activity",
                        "sex",
                        "start time",
                        "team"
                    ],
                    "physicalSampleAvailability" => [
                        ""
                    ],
                    "publisherName" => "Runolfsdottir-Reinger",
                    "shortTitle" => "Admitted Patient Care Dataset",
                    "startDate" => null,
                    "title" => "Admitted Patient Care Dataset",
                    "created_at" => "2024-02-13T12 =>26 =>20.000000Z"
                ],
                "highlight" => [
                    "abstract" => [
                        "Nationaly defined dataset which ontaining administrative details for inpatient admissions (elective, emergency and maternity) and good coverage of clinical coding of diagnosis (<em>ICD10</em>) and procedures (OPCS4)."
                    ],
                    "description" => [
                        "Nationaly defined dataset which ontaining administrative details for inpatient admissions (elective, emergency and maternity) and good coverage of clinical coding of diagnosis (<em>ICD10</em>) and procedures (OPCS4)."
                    ]
                ],
                "metadata" => [
                    "required" => [
                        "gatewayId" => "508",
                        "gatewayPid" => "c3c8e80c-f9d9-4efa-9548-38dce41f209a",
                        "issued" => "2024-02-13T12 =>26 =>20.361415Z",
                        "modified" => "2024-02-13T12 =>26 =>20.361437Z",
                        "revisions" => [],
                        "version" => "5.0.0"
                    ],
                    "summary" => [
                        "abstract" => "The NJR datasets collect continuous, prospective data on patients undergoing primary and revision total joint replacement (hip, knee, shoulder, elbow and ankle). The dataset covers the period 2003 to present and covers the UK and beyond",
                        "contactPoint" => "research@njr.org.uk",
                        "keywords" => "Replacement,Joint,NJR,Ankle,Primary,Registry",
                        "controlledKeywords" => null,
                        "datasetType" => "Healthdata",
                        "description" => "The National Joint Registry for England, Wales, Northern Ireland and the Isle of Man is a database containing details of all primary and revision total hip, knee, shoulder, elbow and ankle replacement procedures carried out in NHS and independent sector hospitals in England, Wales, Northern Ireland and the Isle of Man.  Primary hip replacement has its own specific dataset for procedures.\nInitially this data is collected during a patient's time at hospital as part of bespoke data collection to support the NJR . This is submitted to NEC Software Solutions (contracted to the NJR) for processing and is used to monitor the quality and safety of patient care and outcomes. \nThis same data can also be processed and used for non-clinical purposes, such as research and planning health services. Because these uses are not to do with direct patient care, they are called 'secondary uses'. This is the NJR research ready data set.\nNJR data covers all procedures carried out in NHS and independent sector hospitals  in England, Wales (from 2003), Northern Ireland )from 2013), the Isle of Man (from 2015) and the States of Guernsey (from 2019).\nEach NJR record contains a wide range of information about an individual patient treated at an NHS or independent sector hospital, including =>\n• clinical information about surgical indications and operations\n• patient information, such as age group and gender\n• component information, such as the brand and size of prosthesis used\n• outcomes, such as whether a revision procedure has been undertaken\nNJR apply a strict statistical disclosure control in accordance with the NHS Digital protocol, to all published NJR data. This suppresses small numbers to stop people identifying themselves and others, to ensure that patient confidentiality is maintained.\nWho NJR data is for\nNJR can provide data for the purpose of healthcare analysis to the NHS, government and others including =>\n• national bodies and regulators, such as the Department of Health, NHS England, Public Health England, NHS Improvement and the CQC\n• local Clinical Commissioning Groups (CCGs)\n• provider organisations\n• government departments\n• researchers and commercial healthcare bodies\n• National Institute for Clinical Excellence (NICE)\n• patients, service users and carers\n• the media\nUses of the statistics\nThe statistics are known to be used for =>\n• national policy making\n• benchmarking performance against other hospital providers or CCGs  \n• academic research\n• analysing service usage and planning change\n• providing advice to ministers and answering a wide range of parliamentary questions\n• national and local press articles\n• international comparison\nMore information can be found at http =>//www.njrcentre.org.uk/njrcentre/",
                        "doiName" => null,
                        "shortTitle" => "National Joint Registry - Primary Ankle Replacement dataset",
                        "title" => "National Joint Registry - Primary Ankle Replacement dataset",
                        "publisher" => [
                            "gatewayId" => "bbe40a5e-b568-4588-9ffa-190bd5806e55",
                            "name" => "HQIP"
                        ],
                        "populationSize" => 0,
                        "datasetSubType" => null
                    ],
                    "coverage" => [
                        "pathway" => "Data is representative of the patient pathway. Readmissions for reasons other than revision surgery are not collected.",
                        "spatial" => "Isle of Man,Guernsey,Guernsey,United Kingdom,England,United Kingdom,Wales,United Kingdom,Northern Ireland",
                        "followup" => "UNKNOWN",
                        "typicalAgeRange" => "0-150",
                        "biologicalsamples" => null,
                        "gender" => null,
                        "psychological" => null,
                        "physical" => null,
                        "anthropometric" => null,
                        "lifestyle" => null,
                        "socioeconomic" => null
                    ],
                    "provenance" => [
                        "origin" => [
                            "purpose" => "AUDIT,DISEASE REGISTRY,OTHER",
                            "source" => "EPR,ELECTRONIC SURVEY,PAPER BASED",
                            "collectionSituation" => "IN-PATIENTS,PRIVATE"
                        ],
                        "temporal" => [
                            "endDate" => null,
                            "startDate" => "2010-04-01",
                            "timeLag" => "VARIABLE",
                            "accrualPeriodicity" => "CONTINUOUS",
                            "distributionReleaseDate" => null
                        ]
                    ],
                    "accessibility" => [
                        "access" => [
                            "deliveryLeadTime" => null,
                            "jurisdiction" => "GB-EAW",
                            "dataController" => "Healthcare Quality Improvement Partnership jointly with NHS England",
                            "dataProcessor" => "NEC Software Solutions",
                            "accessRights" => "https =>//www.njrcentre.org.uk/research/research-requests/,https =>//www.hqip.org.uk/national-programmes/accessing-ncapop-data",
                            "accessService" => null,
                            "accessRequestCost" => "https =>//www.njrcentre.org.uk/wp-content/uploads/NJR-cost-recovery-policy-April-2019-v1.0.pdf"
                        ],
                        "usage" => [
                            "dataUseLimitation" => "NO RESTRICTION",
                            "dataUseRequirement" => "PROJECT SPECIFIC RESTRICTIONS",
                            "resourceCreator" => [
                                "name" => "the study title should be followed by the suffix => ‘An analysis from the National Joint Registry'.",
                                "gatewayId" => null,
                                "rorId" => null
                            ]
                        ],
                        "formatAndStandards" => [
                            "vocabularyEncodingSchemes" => "LOCAL",
                            "conformsTo" => "LOCAL",
                            "languages" => "en",
                            "formats" => "Tab delimited file made available via NJR Data Access Portal"
                        ]
                    ],
                    "linkage" => [
                        "associatedMedia" => "https =>//www.njrcentre.org.uk/research/research-requests/",
                        "isReferenceIn" => null,
                        "tools" => null,
                        "datasetLinkage" => [
                            "isDerivedFrom" => "Not available",
                            "isPartOf" => "NJR",
                            "linkedDatasets" => "HES - available subject to additional permissions,National PROMS - available subject to additional permissions,Civil Registration Data - available subject to additional permissions",
                            "isMemberOf" => null
                        ],
                        "investigations" => "https =>//www.njrcentre.org.uk/research/research-requests/",
                        "isGeneratedUsing" => null,
                        "dataUses" => null
                    ],
                    "observations" => [
                        [
                            "observedNode" => "FINDINGS",
                            "measuredValue" => 6589,
                            "observationDate" => "2020-09-01",
                            "measuredProperty" => "Count",
                            "disambiguatingDescription" => "number of primary ankle  replacements during 2019 in the dataset"
                        ]
                    ],
                    "structuralMetadata" => [],
                    "tissuesSampleCollection" => null
                ],
                "original_metadata" => [
                    "identifier" => "https =>//web.www.healthdatagateway.org/b884fead-1e03-4ce2-9d17-3632b0eedb02",
                    "version" => "5.0.0",
                    "issued" => "2023-03-02T00 =>00 =>00.000Z",
                    "modified" => "2023-03-02T00 =>00 =>00.000Z",
                    "revisions" => [],
                    "summary" => [
                        "title" => "National Joint Registry - Primary Ankle Replacement dataset",
                        "abstract" => "The NJR datasets collect continuous, prospective data on patients undergoing primary and revision total joint replacement (hip, knee, shoulder, elbow and ankle). The dataset covers the period 2003 to present and covers the UK and beyond",
                        "publisher" => [
                            "identifier" => "https =>//web.www.healthdatagateway.org/607db9c4e1f9d3704d570d23",
                            "name" => "HQIP",
                            "logo" => null,
                            "description" => null,
                            "contactPoint" => null,
                            "memberOf" => "ALLIANCE"
                        ],
                        "contactPoint" => "research@njr.org.uk",
                        "keywords" => "Replacement,Joint,NJR,Ankle,Primary,Registry",
                        "alternateIdentifiers" => null,
                        "doiName" => null
                    ],
                    "documentation" => [
                        "description" => "The National Joint Registry for England, Wales, Northern Ireland and the Isle of Man is a database containing details of all primary and revision total hip, knee, shoulder, elbow and ankle replacement procedures carried out in NHS and independent sector hospitals in England, Wales, Northern Ireland and the Isle of Man.  Primary hip replacement has its own specific dataset for procedures.\nInitially this data is collected during a patient's time at hospital as part of bespoke data collection to support the NJR . This is submitted to NEC Software Solutions (contracted to the NJR) for processing and is used to monitor the quality and safety of patient care and outcomes. \nThis same data can also be processed and used for non-clinical purposes, such as research and planning health services. Because these uses are not to do with direct patient care, they are called 'secondary uses'. This is the NJR research ready data set.\nNJR data covers all procedures carried out in NHS and independent sector hospitals  in England, Wales (from 2003), Northern Ireland )from 2013), the Isle of Man (from 2015) and the States of Guernsey (from 2019).\nEach NJR record contains a wide range of information about an individual patient treated at an NHS or independent sector hospital, including =>\n• clinical information about surgical indications and operations\n• patient information, such as age group and gender\n• component information, such as the brand and size of prosthesis used\n• outcomes, such as whether a revision procedure has been undertaken\nNJR apply a strict statistical disclosure control in accordance with the NHS Digital protocol, to all published NJR data. This suppresses small numbers to stop people identifying themselves and others, to ensure that patient confidentiality is maintained.\nWho NJR data is for\nNJR can provide data for the purpose of healthcare analysis to the NHS, government and others including =>\n• national bodies and regulators, such as the Department of Health, NHS England, Public Health England, NHS Improvement and the CQC\n• local Clinical Commissioning Groups (CCGs)\n• provider organisations\n• government departments\n• researchers and commercial healthcare bodies\n• National Institute for Clinical Excellence (NICE)\n• patients, service users and carers\n• the media\nUses of the statistics\nThe statistics are known to be used for =>\n• national policy making\n• benchmarking performance against other hospital providers or CCGs  \n• academic research\n• analysing service usage and planning change\n• providing advice to ministers and answering a wide range of parliamentary questions\n• national and local press articles\n• international comparison\nMore information can be found at http =>//www.njrcentre.org.uk/njrcentre/",
                        "associatedMedia" => "https =>//www.njrcentre.org.uk/research/research-requests/",
                        "isPartOf" => "NJR"
                    ],
                    "coverage" => [
                        "spatial" => "Isle of Man,Guernsey,Guernsey,United Kingdom,England,United Kingdom,Wales,United Kingdom,Northern Ireland",
                        "typicalAgeRange" => "0-150",
                        "physicalSampleAvailability" => null,
                        "followup" => "UNKNOWN",
                        "pathway" => "Data is representative of the patient pathway. Readmissions for reasons other than revision surgery are not collected."
                    ],
                    "provenance" => [
                        "origin" => [
                            "purpose" => "AUDIT,DISEASE REGISTRY,OTHER",
                            "source" => "EPR,ELECTRONIC SURVEY,PAPER BASED",
                            "collectionSituation" => "IN-PATIENTS,PRIVATE"
                        ],
                        "temporal" => [
                            "accrualPeriodicity" => "CONTINUOUS",
                            "distributionReleaseDate" => null,
                            "startDate" => "2010-04-01",
                            "endDate" => null,
                            "timeLag" => "VARIABLE"
                        ]
                    ],
                    "accessibility" => [
                        "usage" => [
                            "dataUseLimitation" => "NO RESTRICTION",
                            "dataUseRequirements" => "PROJECT SPECIFIC RESTRICTIONS",
                            "resourceCreator" => "the study title should be followed by the suffix => ‘An analysis from the National Joint Registry'.",
                            "investigations" => "https =>//www.njrcentre.org.uk/research/research-requests/",
                            "isReferencedBy" => null
                        ],
                        "access" => [
                            "accessRights" => "https =>//www.njrcentre.org.uk/research/research-requests/,https =>//www.hqip.org.uk/national-programmes/accessing-ncapop-data",
                            "accessService" => null,
                            "accessRequestCost" => "https =>//www.njrcentre.org.uk/wp-content/uploads/NJR-cost-recovery-policy-April-2019-v1.0.pdf",
                            "deliveryLeadTime" => null,
                            "jurisdiction" => "GB-EAW",
                            "dataProcessor" => "NEC Software Solutions",
                            "dataController" => "Healthcare Quality Improvement Partnership jointly with NHS England"
                        ],
                        "formatAndStandards" => [
                            "vocabularyEncodingScheme" => "LOCAL",
                            "conformsTo" => "LOCAL",
                            "language" => "en",
                            "format" => "Tab delimited file made available via NJR Data Access Portal"
                        ]
                    ],
                    "enrichmentAndLinkage" => [
                        "qualifiedRelation" => "HES - available subject to additional permissions,National PROMS - available subject to additional permissions,Civil Registration Data - available subject to additional permissions",
                        "derivation" => "Not available",
                        "tools" => null
                    ],
                    "observations" => [
                        [
                            "observedNode" => "FINDINGS",
                            "measuredValue" => 6589,
                            "measuredProperty" => "Count",
                            "observationDate" => "2020-09-01",
                            "disambiguatingDescription" => "number of primary ankle  replacements during 2019 in the dataset"
                        ]
                    ],
                    "structuralMetadata" => [],
                ],
                "gwdmVersion" => "1.1",
                "isCohortDiscovery" => false
            ]
        ];
    }
}
