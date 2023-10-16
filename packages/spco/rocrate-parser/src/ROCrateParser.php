<?php

namespace Spco\ROCrateParser;


$loader = require __DIR__ . '/../vendor/autoload.php';
// use vendor\JsonLD;
// var_dump($loader);
// var_dump($v);

function iterateNodes(array $nodes, $maxDepth = 1, $prefix = "")
{
    if ($maxDepth === 0) {
        echo "Hit MaxDepth\n";
        return;
    }

    /**
     * @var \ML\JsonLD\Node $node
     */
    foreach ($nodes as $node) {
        echo "\n";
        echo $prefix . "ID: ".$node->getId()."\n";
        /**
         * @var \ML\JsonLD\Node[] $properties
         */
        $properties = $node->getProperties();
        iterateNodes($properties, $maxDepth - 1, $prefix . "  ");
    }
}

function filterArrayProject($value){
    return (isset($value["@type"]) && $value["@type"] == "Project");
}


class ROCrateParser {
    public static function justDoIt() {
        return "testing!";
    }

    public static function print(string $ro_crate) {
        var_dump($ro_crate);
    }

    public static function expand(string $ro_crate) {
        $v = new \ML\JsonLD\JsonLD;
        $j = <<<JSON_LD_DOCUMENT
{
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
}  
JSON_LD_DOCUMENT;

        // "{\r\n    \"@context\": \"https:\/\/w3id.org\/ro\/crate\/1.2-DRAFT\/context\",\r\n    \"@graph\": [\r\n        {\r\n            \"@type\": \"CreativeWork\",\r\n            \"@id\": \"ro-crate-metadata.json\",\r\n            \"about\": {\r\n                \"@id\": \".\/\"\r\n            },\r\n            \"conformsTo\": {\r\n                \"@id\": \"https:\/\/w3id.org\/ro\/crate\/1.2-DRAFT\"\r\n            }\r\n        },\r\n        {\r\n            \"@id\": \".\/\",\r\n            \"@type\": \"Dataset\",\r\n            \"conformsTo\": {\r\n                \"@id\": \"https:\/\/w3id.org\/5s-crate\/0.4\"\r\n            },\r\n            \"hasPart\": [\r\n                {\r\n                    \"@id\": \"https:\/\/workflowhub.eu\/workflows\/289?version=1\"\r\n                },\r\n                {\r\n                    \"@id\": \"input1.txt\"\r\n                }\r\n            ],\r\n            \"mainEntity\": {\r\n                \"@id\": \"https:\/\/workflowhub.eu\/workflows\/289?version=1\"\r\n            },\r\n            \"mentions\": {\r\n                \"@id\": \"#query-37252371-c937-43bd-a0a7-3680b48c0538\"\r\n            },\r\n            \"sourceOrganization\": {\r\n                \"@id\": \"#project-be6ffb55-4f5a-4c14-b60e-47e0951090c70\"\r\n            }\r\n        },\r\n        {\r\n            \"@id\": \"https:\/\/w3id.org\/5s-crate\/0.4\",\r\n            \"@type\": \"Profile\",\r\n            \"name\": \"Five Safes RO-Crate profile\"\r\n        },\r\n        {\r\n            \"@id\": \"https:\/\/workflowhub.eu\/workflows\/289?version=1\",\r\n            \"@type\": \"Dataset\",\r\n            \"name\": \"CWL Protein MD Setup tutorial with mutations\",\r\n            \"conformsTo\": {\r\n                \"@id\": \"https:\/\/w3id.org\/workflowhub\/workflow-ro-crate\/1.0\"\r\n            },\r\n            \"distribution\": {\r\n                \"@id\": \"https:\/\/workflowhub.eu\/workflows\/289\/ro_crate?version=1\"\r\n            }\r\n        },\r\n        {\r\n            \"@id\": \"https:\/\/workflowhub.eu\/workflows\/289\/ro_crate?version=1\",\r\n            \"@type\": \"DataDownload\",\r\n            \"conformsTo\": {\r\n                \"@id\": \"https:\/\/w3id.org\/ro\/crate\"\r\n            },\r\n            \"encodingFormat\": \"application\/zip\"\r\n        },\r\n        {\r\n            \"@id\": \"#query-37252371-c937-43bd-a0a7-3680b48c0538\",\r\n            \"@type\": \"CreateAction\",\r\n            \"actionStatus\": \"http:\/\/schema.org\/PotentialActionStatus\",\r\n            \"agent\": {\r\n                \"@id\": \"https:\/\/orcid.org\/0000-0001-9842-9718\"\r\n            },\r\n            \"instrument\": {\r\n                \"@id\": \"https:\/\/workflowhub.eu\/workflows\/289?version=1\"\r\n            },\r\n            \"name\": \"Execute query 12389 on workflow \",\r\n            \"object\": [\r\n                {\r\n                    \"@id\": \"input1.txt\"\r\n                },\r\n                {\r\n                    \"@id\": \"#enableFastMode\"\r\n                }\r\n            ]\r\n        },\r\n        {\r\n            \"@id\": \"https:\/\/orcid.org\/0000-0001-9842-9718\",\r\n            \"@type\": \"Person\",\r\n            \"name\": \"Stian Soiland-Reyes\",\r\n            \"affiliation\": {\r\n                \"@id\": \"https:\/\/ror.org\/027m9bs27\"\r\n            },\r\n            \"memberOf\": [\r\n                {\r\n                    \"@id\": \"#project-be6ffb55-4f5a-4c14-b60e-47e0951090c70\"\r\n                }\r\n            ]\r\n        },\r\n        {\r\n            \"@id\": \"https:\/\/ror.org\/027m9bs27\",\r\n            \"@type\": \"Organization\",\r\n            \"name\": \"The University of Manchester\"\r\n        },\r\n        {\r\n            \"@id\": \"#project-be6ffb55-4f5a-4c14-b60e-47e0951090c70\",\r\n            \"@type\": \"Project\",\r\n            \"name\": \"Investigation of cancer (TRE72 project 81)\",\r\n            \"identifier\": [\r\n                {\r\n                    \"@id\": \"_:localid:tre72:project81\"\r\n                }\r\n            ],\r\n            \"funding\": {\r\n                \"@id\": \"https:\/\/gtr.ukri.org\/projects?ref=10038961\"\r\n            },\r\n            \"member\": [\r\n                {\r\n                    \"@id\": \"https:\/\/ror.org\/027m9bs27\"\r\n                },\r\n                {\r\n                    \"@id\": \"https:\/\/ror.org\/01ee9ar58\"\r\n                }\r\n            ]\r\n        },\r\n        {\r\n            \"@id\": \"_:localid:tre72:project81\",\r\n            \"@type\": \"PropertyValue\",\r\n            \"name\": \"tre72\",\r\n            \"value\": \"project81\"\r\n        },\r\n        {\r\n            \"@id\": \"https:\/\/gtr.ukri.org\/projects?ref=10038961\",\r\n            \"@type\": \"Grant\",\r\n            \"name\": \"EOSC4Cancer\"\r\n        },\r\n        {\r\n            \"@id\": \"input1.txt\",\r\n            \"@type\": \"File\",\r\n            \"name\": \"input1\",\r\n            \"exampleOfWork\": {\r\n                \"@id\": \"#sequence\"\r\n            }\r\n        },\r\n        {\r\n            \"@id\": \"#enableFastMode\",\r\n            \"@type\": \"PropertyValue\",\r\n            \"name\": \"--fast-mode\",\r\n            \"value\": \"True\",\r\n            \"exampleOfWork\": {\r\n                \"@id\": \"#fast\"\r\n            }\r\n        },\r\n        {\r\n            \"@id\": \"#sequence\",\r\n            \"@type\": \"FormalParameter\",\r\n            \"name\": \"input-sequence\"\r\n        },\r\n        {\r\n            \"@id\": \"#fast\",\r\n            \"@type\": \"FormalParameter\",\r\n            \"name\": \"fast-mode\"\r\n        }\r\n    ]\r\n}";
        // $je = $v->toRdf($j);
        // var_dump($je);
        // var_dump($v);
        

        $doc = $v->getDocument($j);

        // get the default graph
        $graph = $doc->getGraph();

        // get all nodes in the graph
        $nodes = $graph->getNodes();
        $node_name = "ro-crate-metadata.json";
        $n = $graph->getNode($node_name);
        var_dump($graph->containsNode($node_name));

        // iterateNodes($graph->getNodes());

        // foreach ($nodes as $node) {
        //     var_dump('this is a node');
        //     var_dump($node->getId());
        //     $comp_node = $graph->getNode($node->getId());
        //     var_dump('compared to');
        //     var_dump($node->getId());
        //     var_dump('');
        //     // var_dump($node->getType());
        // }

        $json_raw = json_decode($j, true);
        // Get graph contents
        // var_dump($json_raw["@graph"]);

        // foreach ($json_raw["@graph"] as $object) {
        //     if (isset($object["@type"]) && $object["@type"] == "Dataset" && isset($object["sourceOrganization"])) {
        //         print "This is a Dataset\n";
        //         var_dump($object);
        //         print "\n with sourceOrganization:\n";
        //         $sourceOrganization = $object["sourceOrganization"]["@id"];
        //         print $sourceOrganization . "\n";
        //     }
            
        // }

        $myArray = array();
        foreach ($json_raw["@graph"] as $object) {
            $myArray[$object["@id"]] = $object;
        }
        // var_dump($myArray);


        // Find project_title, and if supplied, lay_summary and public_benefit_statement.
        foreach ($myArray as $object) {
            if (isset($object["@type"]) && $object["@type"] == "Dataset" && isset($object["sourceOrganization"])) {
                print "This is a Dataset\n";
                var_dump($object);
                print "\nwith sourceOrganization:\n";
                $sourceOrganization = $object["sourceOrganization"]["@id"];
                print $sourceOrganization . "\n";
            }
        }
        print "This points to this Project:\n";
        if (isset($myArray[$sourceOrganization]) && $myArray[$sourceOrganization]["@type"] == 'Project') {
            var_dump($myArray[$sourceOrganization]);
            print "Which has name:\n";
            // $project_title = $myArray[$sourceOrganization]["name"];
            var_dump($project_title = $myArray[$sourceOrganization]["name"]);

            if (isset($myArray[$sourceOrganization]["description"])) {
                $lay_summary = $myArray[$sourceOrganization]["description"];
            }
            else {
                $lay_summary = "Not provided";
            }

            if (isset($myArray[$sourceOrganization]["publishingPrinciples"]) && 
            isset($myArray[$myArray[$sourceOrganization]["publishingPrinciples"]["@id"]]) &&
            isset($myArray[$myArray[$myArray[$sourceOrganization]["publishingPrinciples"]["@id"]]]["text"])) {
                $public_benefit_statement = $myArray[$myArray[$myArray[$sourceOrganization]["publishingPrinciples"]["@id"]]]["text"];
            }
            else
            {
                $public_benefit_statement = "Not provided";
            }
            

        }

        // Find organization_name
        foreach ($myArray as $object) {
            if (isset($object["@type"]) && $object["@type"] == "Dataset" && isset($object["mentions"])) {
                print "This is a Dataset\n";
                var_dump($object);
                print "\nwith mentions:\n";
                $mentions = $object["mentions"]["@id"];
                print $mentions . "\n";
            }
        }
        print "This points to this CreateAction:\n";
        if (isset($myArray[$mentions]) && $myArray[$mentions]["@type"] == 'CreateAction') {
            var_dump($myArray[$mentions]);
            print "Which has agent:\n";
            // $project_title = $myArray[$sourceOrganization]["name"];
            var_dump($createAction_agent_id = $myArray[$mentions]["agent"]["@id"]);
            var_dump($agent = $myArray[$createAction_agent_id]);
            print "with affiliation:\n";
            var_dump($affiliation_id = $myArray[$createAction_agent_id]["affiliation"]["@id"]);
            var_dump($affiliation = $myArray[$affiliation_id]);
            print "with Org name:\n";
            var_dump($organization_name = $affiliation["name"]);

            // var_dump($myArray[$agent_id]);
        }

        // // var_dump(filterArray($json_raw["@graph"]));
        // $projectArray = array_values(array_filter($myArray, 'Spco\ROcrateParser\filterArrayProject'))[0];
        // print "This is the Project object:\n";
        // var_dump($projectArray);
        // print "And this is the Project.name, to be returned as `project_title`:\n";
        // var_dump($projectArray["name"]);

        

        $return_array = array();
        $return_array["organization_name"] = $organization_name;
        $return_array["project_title"] = $project_title;
        $return_array["lay_summary"] = $lay_summary;
        $return_array["public_benefit_statement"] = $public_benefit_statement;

        var_dump($return_array);
        // var_dump($j->getGraph);
    }
}