<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Carbon\Carbon;
use App\Models\Dur;
use App\Models\DurHasDatasetVersion;
use App\Models\DurHasKeyword;
use App\Models\DurHasUser;
use App\Models\Keyword;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nonGatewayDatasets = [
            'HES Outpatient',
            'HES-ID to MPS-ID HES Accident and Emergency',
            'HES-ID to MPS-ID HES Admitted Patient Care',
            'HES-ID to MPS-ID HES Outpatients',
            'Housing Executive Data (external)',
            'I AM Testing',
            'ICARE',
            'ICHNT -COVID-19',
            'ICNARC',
            'ICNARC (COVID-19 Intensive Care National Audit and Research Centre)',
            'INSIGHT Datasets',
            'ISARIC Clinical Data (Redcap)',
            'Intensive Care National Audit and Research Centre (ICNC)',
            'Intensive Care National Audit and Research Centre - Covid19 (ICCD)',
            'International Trade in Services',
        ];
        $fundersAndSponsors = [
            'National Institute for Health Research',
            'National Institute for Health Research Collaboration for Leadership in Applied Health Research & Care- West',
            'National Perinatal Epidemiology Unit',
            'Newcastle Upon Tyne Hospitals NHS Foundation Trust',
            'Newlife Foundation for Disabled Children',
            'North East Quality Observatory System',
            'Nottingham NIHR BRC',
            'Novo Nordic Foundation ',
            'Office for National Statistics',
            'PCL/18/05)',
        ];
        $otherApprovalCommittees = [
            'REC reference: 13/SC/0124',
            'REC reference: 13/SW/0339',
            'REC reference: 14/LO/1965',
            'REC reference: 14/NW/0349',
            'REC reference: 15/NW/0503',
            'REC reference: 15/SW/0294',
            'REC reference: 16/EM/0351',
            'REC reference: 16/WA/0324',
        ];
        $nonGatewayOutputs = [
            'https://doi.org/10.1161/CIRCULATIONAHA.122.060785',
            'https://doi.org/10.1177/01410768221131897',
            'https://doi.org/10.1186/s12911-022-02093-0',
            'https://doi.org/10.1371/journal.pmed.1003926',
            'https://doi.org/10.2139/ssrn.3789264',
            'https://emj.bmj.com/content/38/9/A2.2',
            'https://eprints.whiterose.ac.uk/177210/',
        ];
        $projectTitle = [
            'BPD (bleeding and platelet disorders)',
            'Beta-adrenergic receptor expression and beta-blocker drug use: association with breast cancer survival',
            'Bidirectional effects of educational attainment/intelligence on brain morphology',
            'Biodiversity inflammation and aversive bodily symptoms',
            'Biological markers to study genetics environment and how they influence mental health',
            'Birth order and cord blood DNA methylation',
            'Bliss Staffing Study: Neonatal Economic, Staffing and Clinical Outcomes Project (NESCOP)',
        ];
        $projectIdText = [
            'B3253',
            'B3649',
            'B3682',
            'B3683',
            'B3684',
            'B3686',
        ];
        $organisationName = [
            'Kings College London',
            'Kirklees Council',
            'LA-SER Europe Ltd',
            'Leeds Teaching Hospitals NHS Trust',
            'Liverpool University',
        ];
        $organisationSector = [
            'CQC Registered Health or/and Social Care provider',
            'Commercial',
            'Government Agency (Health and Adult Social Care)',
            'Government Agency (Other)',
            'Independent Sector Organisation',
            'Local authority',
        ];
        $dataSensitivityLevel = [
            '',
            'Anonymous',
            'De-Personalised',
            'De-personalised',
            'Personally Identifiable'
        ];
        $dutyOfConfidentiality = [
            '',
            'Consent',
            'Not applicable',
            'Section 251 NHS Act 2006',
            'Section 251 support',
            'Statutory exemption to flow confidential data without consent',
            'The individual to whom the information relates has consented'
        ];
        $nationalDataOptOut = [
            'Consent (Reasonable Expectation)',
            'Does not include the flow of confidential data',
            'No',
            'Not applicable',
            'Statutory exemption to flow confidential data without consent',
        ];
        $organisationId = [
            'grid.10025.36',
            'grid.10223.32', 
            'grid.10306.34',
            'grid.10586.3a', 
            'grid.11201.33',
            'grid.120073.7', 
            'grid.239585.0',
        ];
        $requestCategoryType = [
            'Efficacy & Mechanism Evaluation',
            'Health Services & Delivery',
            'Other',
            'Public Health Research',
            'Research',
        ];
        $requestFrequency = [
            'Efficacy & Mechanism Evaluation',
            'Health Services & Delivery',
            'Other',
            'Public Health Research',
            'Research'
        ];
        $accessType = [
            'Efficacy & Mechanism Evaluation',
            'Health Services & Delivery',
            'Other',
            'Public Health Research',
            'Research',
        ];

        for ($i = 1; $i <= 15; $i++) {
            $userId = User::all()->random()->id;
            $teamId = Team::all()->random()->id;
            $keywordId = Keyword::all()->random()->id;
            $datasetVersionId = Dataset::all()->random()->latestVersion()->id;
            $arrayDur =
            [
                'non_gateway_datasets' => [fake()->randomElement($nonGatewayDatasets)], // nonGatewayDatasets
                'non_gateway_applicants' => [fake()->firstName() . ' ' . fake()->lastName()],
                'funders_and_sponsors' => [fake()->randomElement($fundersAndSponsors)], // fundersAndSponsors
                'other_approval_committees' => [fake()->randomElement($otherApprovalCommittees)], // otherApprovalCommittees
                'gateway_outputs_tools' => [],
                'non_gateway_outputs' => [fake()->randomElement($nonGatewayOutputs)], // nonGatewayOutputs
                'gateway_outputs_papers' => [], // gatewayOutputsPapers
                'project_title' => fake()->randomElement($projectTitle), // projectTitle
                'project_id_text' => fake()->randomElement($projectIdText), // projectIdText
                'organisation_name' => fake()->randomElement($organisationName), // organisationName
                'organisation_sector' => fake()->randomElement($organisationSector), // organisationSector
                'lay_summary' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"), // laySummary
                'technical_summary' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"), // technicalSummary
                'latest_approval_date' => Carbon::now(), // latestApprovalDate
                'manual_upload' => fake()->randomElement([0, 1]), // manualUpload

                'rejection_reason' => '', // rejectionReason
                'sublicence_arrangements' => '', // sublicenceArrangements
                'public_benefit_statement' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"), // publicBenefitStatement
                'data_sensitivity_level' => fake()->randomElement($dataSensitivityLevel), // dataSensitivityLevel

                'project_start_date' => Carbon::now()->addDays(1), // projectStartDate
                'project_end_date' => Carbon::now()->addDays(5), // projectEndDate

                'access_date' => Carbon::now(), // accessDate - seems like is a relation with counter

                'accredited_researcher_status' => fake()->randomElement(['', 'No', 'Unknown', 'Yes']), // accreditedResearcherStatus
                'confidential_data_description' => '', // confidentialDataDescription
                'dataset_linkage_description' => '', // datasetLinkageDescription
                'duty_of_confidentiality' => fake()->randomElement($dutyOfConfidentiality), // dutyOfConfidentiality

                'legal_basis_for_data_article6' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"), // legalBasisForDataArticle6
                'legal_basis_for_data_article9' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"), // legalBasisForDataArticle9

                'national_data_optout' => fake()->randomElement($nationalDataOptOut), // nationalDataOptOut
                'organisation_id' => fake()->randomElement($organisationId), // organisationId
                'privacy_enhancements' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"), // privacyEnhancements
                'request_category_type' => fake()->randomElement($requestCategoryType), // requestCategoryType
                'request_frequency' => fake()->randomElement($requestFrequency), // requestFrequency
                'access_type' => fake()->randomElement($accessType), // accessType
                'mongo_object_dar_id' => fake()->numerify('MOBJIDDAR-####'), // projectId which is data_requests._id (mongo)

                'user_id' => $userId, // user: from team
                'team_id' => $teamId, // publisher: from team

                'enabled' => fake()->boolean(), // activeflag
                'last_activity' => Carbon::now(), // lastActivity
                'counter' => fake()->randomNumber(5, false), // counter

                'mongo_object_id' => fake()->numerify('MOBJID-####'), // _id (mongo)
                'mongo_id' => fake()->numberBetween(10000000000, 99999999999), // id

                'status' => fake()->randomElement([
                    Dur::STATUS_ACTIVE,
                    Dur::STATUS_DRAFT,
                    Dur::STATUS_ARCHIVED
                ]),
            ];
            $dur = Dur::create($arrayDur);

            DurHasKeyword::create([
                'dur_id' => $dur->id,
                'keyword_id' => $keywordId,
            ]);
        }
    }
}
