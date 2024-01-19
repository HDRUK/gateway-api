<?php

namespace Database\Demo;

use Exception;
use App\Models\Team;
use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatasetDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // team_id 1
            [
                'team_id' => 1,
                'user_id' => 3,
                'title' => 'NeoScan Medical Imaging Repository',
                'short_description' => 'Diverse medical imaging data for diagnostic research and AI algorithm training.',
                'description' => 'NeoScan offers a wide range of de-identified medical images, such as X-rays, MRIs, and CT scans. It serves as a valuable resource for diagnostic research and training machine learning algorithms for medical image analysis.',
                'create_origin' => 'MANUAL',
                'status' => 'ACTIVE',
            ],
            [
                'team_id' => 1,
                'user_id' => 3,
                'title' => 'MedGenome Disease Gene Variants',
                'short_description' => 'Explore genetic variants linked to various diseases for research and precision medicine.',
                'description' => 'This dataset catalogs genetic variants associated with a wide range of diseases. It supports genetic research, disease diagnostics, and personalized medicine development by providing insights into disease genetics and potential therapeutic targets.',
                'create_origin' => 'MANUAL',
                'status' => 'DRAFT',
            ],

            // team_id 2
            [
                'team_id' => 2,
                'user_id' => 7,
                'title' => 'DrugWatch Clinical Trial Adverse Events',
                'short_description' => 'Clinical trial adverse event records for pharmaceutical safety assessments.',
                'description' => 'DrugWatch compiles adverse event reports from pharmaceutical clinical trials, providing insights into drug safety profiles. It supports pharmacovigilance research, regulatory assessments, and drug safety evaluations.',
                'create_origin' => 'MANUAL',
                'status' => 'ACTIVE',
            ],
            [
                'team_id' => 2,
                'user_id' => 7,
                'title' => 'HeartBeat Cardiac Monitoring Data',
                'short_description' => 'Cardiac data for heart health research, featuring ECGs and vital signs.',
                'description' => 'The HeartBeat dataset offers a collection of cardiac monitoring data, including ECG recordings and vital sign measurements. It is valuable for cardiovascular research, arrhythmia detection, and the development of heart health monitoring technologies.',
                'create_origin' => 'MANUAL',
                'status' => 'DRAFT',
            ],

            // team_id 3
            [
                'team_id' => 3,
                'user_id' => 11,
                'title' => 'NeoNeuro EEG Dataset',
                'short_description' => 'EEG recordings for neurology research and brain-computer interface development.',
                'description' => 'NeoNeuro houses a collection of EEG recordings, enabling studies on brain activity, cognitive disorders, and brain-computer interface technologies. Researchers can leverage this dataset for advancements in neurology and brain-related research.',
                'create_origin' => 'MANUAL',
                'status' => 'ACTIVE',
            ],
            [
                'team_id' => 3,
                'user_id' => 11,
                'title' => 'PulmoCare Lung Function Metrics',
                'short_description' => 'Comprehensive lung function data for respiratory studies and disease analysis.',
                'description' => 'PulmoCare provides detailed lung function metrics, including spirometry and gas diffusion results. It supports respiratory research, clinical assessments, and the study of lung diseases like asthma and COPD.',
                'create_origin' => 'MANUAL',
                'status' => 'DRAFT',
            ],
        ];

        $url = env('APP_URL') . '/api/v1/datasets';
        $authorisation = AuthorisationCode::first();

        foreach ($items as $item) {
            try {
                $team = Team::find($item['team_id']);

                if ($team) {
                    $dataset = $this->getFakeDataset($item['title'], $item['short_description'], $item['description'], $team->name, $team->member_of);

                    $payload = [
                        'team_id' => $item['team_id'],
                        'user_id' => $item['user_id'],
                        'label' => $item['title'],
                        'short_description' => $item['short_description'],
                        'dataset' => $dataset,
                        'create_origin' => $item['create_origin'],
                        'status' => $item['status'],
                    ];

                    Http::withHeaders([
                        'Authorization' => 'Bearer ' . $authorisation->jwt,
                        'Content-Type' => 'application/json', // Adjust content type as needed
                    ])->post($url, $payload);
                } else {
                    throw new Exception("Team with id : " . $item['team_id'] . " not found");
                }
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }
        }
    }

    private function getFakeDataset($title, $shortDescription, $description, $teamName, $teamMemberOf)
    {
        return [
            'extra' => [
                'id' => '1234', 
                'pid' => '5124f2',
                'controlledKeyWords' => [
                    'Papers',
                    'COVID-19',
                    'controlledWord'
                ],
                'pathwayDescription' => 'Not APPLICABLE for blah reason',
                'datasetType' => 'list of papers',
                'isGeneratedUsing' => 'something',
                'dataUses' => 'dunno',
                'isMemberOf' => 'blah'
            ],
            'metadata' => [
                'identifier' => 'https://web.www.healthdatagateway.org/dataset/a7ddefbd-31d9-4703-a738-256e4689f76a',
                'version' => '2.0.0',
                'summary' => [
                    'title' => $title,
                    'doiName' => '10.1093/ije/dyx196',
                    'abstract' => $shortDescription,
                    'publisher' => [
                        'name' => $teamName,
                        'memberOf' => $teamMemberOf,
                        'contactPoint' => 'susheel.varma@hdruk.ac.uk'
                    ],
                    'contactPoint' => 'susheel.varma@hdruk.ac.uk',
                    'keywords' => [
                        'Preprints',
                        'Papers',
                        'HDR UK'
                    ]
                ],
                'documentation' => [
                    'description' => $description,
                    'associatedMedia' => [
                        'https://github.com/HDRUK/papers'
                    ],
                    'isPartOf' => 'NOT APPLICABLE'
                ],
                'revisions' => [
                    [
                        'version' => '1.0.0',
                        'url' => 'https://d5faf9c6-6c34-46d7-93c4-7706a5436ed9'
                    ],
                    [
                        'version' => '2.0.0',
                        'url' => 'https://a7ddefbd-31d9-4703-a738-256e4689f76a'
                    ],
                    [
                        'version' => '0.0.1',
                        'url' => 'https://9e798632-442a-427b-8d0e-456f754d28dc'
                    ],
                    [
                        'version' => '2.1.1',
                        'url' => 'https://a7ddefbd-31d9-4703-a738-256e4689f76a'
                    ]
                ],
                'modified' => '2021-01-28T14:15:46Z',
                'issued' => '2020-08-05T14:35:59Z',
                'accessibility' => [
                    'formatAndStandards' => [
                        'language' => 'en',
                        'vocabularyEncodingScheme' => 'OTHER',
                        'format' => [
                            'CSV',
                            'JSON'
                        ],
                        'conformsTo' => 'OTHER'
                    ],
                    'usage' => [
                        'dataUseLimitation' => 'GENERAL RESEARCH USE',
                        'resourceCreator' => 'HDR UK Science Team',
                        'dataUseRequirements' => 'RETURN TO DATABASE OR RESOURCE',
                        'isReferencedBy' => [
                            '10.5281/zenodo.326615'
                        ],
                        'investigations' => [
                            'https://github.com/HDRUK/papers'
                        ]
                    ],
                    'access' => [
                        'dataController' => 'HDR UK',
                        'jurisdiction' => 'GB-ENG',
                        'dataProcessor' => 'HDR UK',
                        'accessService' => 'https://github.com/HDRUK/papers',
                        'accessRights' => 'https://raw.githubusercontent.com/HDRUK/papers/master/LICENSE',
                        'accessRequestCost' => 'Free',
                        'deliveryLeadTime' => 'OTHER'
                    ]
                ],
                'observations' => [
                    [
                        'observedNode' => 'FINDINGS',
                        'measuredValue' => 575,
                        'disambiguatingDescription' => 'Number of papers with affiliation and/or acknowledgement to HDR UK',
                        'observationDate' => '2020-11-27',
                        'measuredProperty' => 'Count'
                    ]
                ],
                'provenance' => [
                    'temporal' => [
                        'endDate' => '2022-04-30',
                        'timeLag' => 'NOT APPLICABLE',
                        'distributionReleaseDate' => '2020-11-27',
                        'accrualPeriodicity' => 'DAILY',
                        'startDate' => '2020-03-31'
                    ],
                    'origin' => [
                        'purpose' => 'OTHER',
                        'source' => 'MACHINE GENERATED',
                        'collectionSituation' => [
                            'OTHER'
                        ]
                    ]
                ],
                'coverage' => [
                    'followup' => 'UNKNOWN',
                    'spatial' => 'https://www.geonames.org/countries/GB/united-kingdom.html',
                    'physicalSampleAvailability' => [
                        'NOT AVAILABLE'
                    ],
                    'pathway' => 'NOT APPLICABLE',
                    'typicalAgeRange' => '0-0'
                ],
                'enrichmentAndLinkage' => [
                    'tools' => [
                        'https://github.com/HDRUK/papers'
                    ],
                    'qualifiedRelation' => [
                        'https://web.www.healthdatagateway.org/dataset/fd8d0743-344a-4758-bb97-f8ad84a37357'
                    ],
                    'derivation' => [
                        'https://web.www.healthdatagateway.org/dataset/fd8d0743-344a-4758-bb97-f8ad84a37357'
                    ]
                ],
                'structuralMetadata' => [
                    [
                        'name' => 'table1',
                        'description' => 'this is table 1',
                        'elements' => [
                            [
                                'name' => 'column1',
                                'description' => 'this is column1',
                                'dataType' => 'String',
                                'sensitive' => false
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

}
