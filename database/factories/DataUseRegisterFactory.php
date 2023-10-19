<?php

namespace Database\Factories;

use App\Models\TeamHasUser;
use App\Models\Dataset;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataUseRegister>
 */
class DataUseRegisterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teamHasUser = TeamHasUser::all()->random();
        $datasetId = Dataset::all()->random()->id;

        $randomString = fake()->words(fake()->randomDigit(), true);

        $fakeROCrate = json_decode('{
            "@context": "https://w3id.org/ro/crate/1.2-DRAFT/context",
            "@graph": [
                {
                    "@type": "CreativeWork",
                    "@id": "ro-crate-metadata.json",
                    "about": {
                        "@id": "./"
                    },
                    "conformsTo": {
                        "@id": "https://w3id.org/ro/crate/1.2-DRAFT"
                    }
                },
                {
                    "@id": "./",
                    "@type": "Dataset",
                    "conformsTo": {
                        "@id": "https://w3id.org/5s-crate/0.4"
                    },
                    "hasPart": [
                        {
                            "@id": "https://workflowhub.eu/workflows/289?version=1"
                        },
                        {
                            "@id": "input1.txt"
                        }
                    ],
                    "mainEntity": {
                        "@id": "https://workflowhub.eu/workflows/289?version=1"
                    },
                    "mentions": {
                        "@id": "#query-37252371-c937-43bd-a0a7-3680b48c0538"
                    },
                    "sourceOrganization": {
                        "@id": "#project-be6ffb55-4f5a-4c14-b60e-47e0951090c70"
                    }
                },
                {
                    "@id": "https://w3id.org/5s-crate/0.4",
                    "@type": "Profile",
                    "name": "Five Safes RO-Crate profile"
                },
                {
                    "@id": "https://workflowhub.eu/workflows/289?version=1",
                    "@type": "Dataset",
                    "name": "CWL Protein MD Setup tutorial with mutations",
                    "conformsTo": {
                        "@id": "https://w3id.org/workflowhub/workflow-ro-crate/1.0"
                    },
                    "distribution": {
                        "@id": "https://workflowhub.eu/workflows/289/ro_crate?version=1"
                    }
                },
                {
                    "@id": "https://workflowhub.eu/workflows/289/ro_crate?version=1",
                    "@type": "DataDownload",
                    "conformsTo": {
                        "@id": "https://w3id.org/ro/crate"
                    },
                    "encodingFormat": "application/zip"
                },
                {
                    "@id": "#query-37252371-c937-43bd-a0a7-3680b48c0538",
                    "@type": "CreateAction",
                    "actionStatus": "http://schema.org/PotentialActionStatus",
                    "agent": {
                        "@id": "https://orcid.org/0000-0001-9842-9718"
                    },
                    "instrument": {
                        "@id": "https://workflowhub.eu/workflows/289?version=1"
                    },
                    "name": "Execute query 12389 on workflow ",
                    "object": [
                        {
                            "@id": "input1.txt"
                        },
                        {
                            "@id": "#enableFastMode"
                        }
                    ]
                },
                {
                    "@id": "https://orcid.org/0000-0001-9842-9718",
                    "@type": "Person",
                    "name": "Stian Soiland-Reyes",
                    "affiliation": {
                        "@id": "https://ror.org/027m9bs27"
                    },
                    "memberOf": [
                        {
                            "@id": "#project-be6ffb55-4f5a-4c14-b60e-47e0951090c70"
                        }
                    ]
                },
                {
                    "@id": "https://ror.org/027m9bs27",
                    "@type": "Organization",
                    "name": "The University of Manchester"
                },
                {
                    "@id": "#project-be6ffb55-4f5a-4c14-b60e-47e0951090c70",
                    "@type": "Project",
                    "name": "Investigation of cancer (TRE72 project 81)",
                    "identifier": [
                        {
                            "@id": "_:localid:tre72:project81"
                        }
                    ],
                    "funding": {
                        "@id": "https://gtr.ukri.org/projects?ref=10038961"
                    },
                    "member": [
                        {
                            "@id": "https://ror.org/027m9bs27"
                        },
                        {
                            "@id": "https://ror.org/01ee9ar58"
                        }
                    ]
                },
                {
                    "@id": "_:localid:tre72:project81",
                    "@type": "PropertyValue",
                    "name": "tre72",
                    "value": "project81"
                },
                {
                    "@id": "https://gtr.ukri.org/projects?ref=10038961",
                    "@type": "Grant",
                    "name": "EOSC4Cancer"
                },
                {
                    "@id": "input1.txt",
                    "@type": "File",
                    "name": "input1",
                    "exampleOfWork": {
                        "@id": "#sequence"
                    }
                },
                {
                    "@id": "#enableFastMode",
                    "@type": "PropertyValue",
                    "name": "--fast-mode",
                    "value": "True",
                    "exampleOfWork": {
                        "@id": "#fast"
                    }
                },
                {
                    "@id": "#sequence",
                    "@type": "FormalParameter",
                    "name": "input-sequence"
                },
                {
                    "@id": "#fast",
                    "@type": "FormalParameter",
                    "name": "fast-mode"
                }
            ]
        }', true);

        return [
            'dataset_id' => $datasetId,
            'enabled' => fake()->boolean(),
            'user_id' => $teamHasUser->user_id,
            'ro_crate' => json_encode($fakeROCrate),
            'organization_name' => $randomString,
            'project_title' => $randomString,
            'lay_summary' => $randomString,
            'public_benefit_statement' => $randomString,
        ];
    }
}
