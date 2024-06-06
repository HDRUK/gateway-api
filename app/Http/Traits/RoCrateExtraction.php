<?php

namespace App\Http\Traits;

trait RoCrateExtraction
{
    public function extractDurDetails(array $ro_crate) {

        // Convert $ro_crate @graph entry to associative array with keys from @id fields.
        $graphArray = [];
        foreach ($ro_crate["@graph"] as $object) {
            $graphArray[$object["@id"]] = $object;
        }

        // Find the id of the source organization
        $sourceOrganization = null;
        foreach ($graphArray as $object) {
            if (isset($object["@type"]) && $object["@type"] == "Dataset" && isset($object["sourceOrganization"])) {
                $sourceOrganization = $object["sourceOrganization"]["@id"];
            }
        }

        // Find project_title, lay_summary and public_benefit_statement from the Project.
        $project_title = null;
        $lay_summary = null;
        $public_benefit_statement = null;
        if (isset($graphArray[$sourceOrganization]) && $graphArray[$sourceOrganization]["@type"] == 'Project') {
            $project_title = $graphArray[$sourceOrganization]["name"];
            // lay_summary = Dataset.sourceOrganization -> Project.description
            // (not guaranteed by 5 Safes RO-Crate spec at this time)
            if (isset($graphArray[$sourceOrganization]["description"])) {
                $lay_summary = $graphArray[$sourceOrganization]["description"];
            }
            else {
                $lay_summary = "Not provided";
            }

            // public_benefit_statement = Dataset.sourceOrganization -> Project.publishingPrinciples -> CreativeWork.text 
            // (not guaranteed by 5 Safes RO-Crate spec at this time)
            if (isset($graphArray[$sourceOrganization]["publishingPrinciples"]) && 
            isset($graphArray[$graphArray[$sourceOrganization]["publishingPrinciples"]["@id"]]) &&
            isset($graphArray[$graphArray[$graphArray[$sourceOrganization]["publishingPrinciples"]["@id"]]]["text"])) {
                $public_benefit_statement = $graphArray[$graphArray[$graphArray[$sourceOrganization]["publishingPrinciples"]["@id"]]]["text"];
            }
            else
            {
                $public_benefit_statement = "Not provided";
            }

        }

        // Find organization_name = Dataset.mentions -> CreateAction.agent -> Person.affiliation -> Organization.name

        // Firstly, find Dataset.
        $mentions = null;
        foreach ($graphArray as $object) {
            if (isset($object["@type"]) && $object["@type"] == "Dataset" && isset($object["mentions"])) {
                $mentions = $object["mentions"]["@id"];
            }
        }

        $organization_name = null;
        if (isset($graphArray[$mentions]) && $graphArray[$mentions]["@type"] == 'CreateAction') {
            $createAction_agent_id = $graphArray[$mentions]["agent"]["@id"];
            $agent = $graphArray[$createAction_agent_id];
            
            $affiliation_id = $graphArray[$createAction_agent_id]["affiliation"]["@id"];
            $affiliation = $graphArray[$affiliation_id];
            
            $organization_name = $affiliation["name"];
        }

        $return_array = [
            "organization_name" => $organization_name,
            "project_title" => $project_title,
            "lay_summary" => $lay_summary,
            "public_benefit_statement" => $public_benefit_statement,
        ];

        return $return_array;
    }
}