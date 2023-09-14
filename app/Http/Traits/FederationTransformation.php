<?php

namespace App\Http\Traits;

use App\Models\Federation;

trait FederationTransformation
{
    public function getFederation(array $teamFederations, int $federationId = 0): array
    {
        $response = [];

        foreach ($teamFederations as $teamFederation) {
            $federation = [];
            
            foreach ($teamFederation['federation'] as $teamFed) {
                if ($federationId && $federationId == $teamFed['id']) {
                    $feds = Federation::where(['id' => $teamFed['id'], 'enabled' => 1])->with(['notification'])->get()->toArray();
                    foreach ($feds as $fed) {
                        $federation[] = $fed;
                    }
                    break;
                }
                if (!$federationId) {
                    $feds = Federation::where(['id' => $teamFed['id'], 'enabled' => 1])->with(['notification'])->get()->toArray();
                    foreach ($feds as $fed) {
                        $federation[] = $fed;
                    }
                }
            }

            if ($federationId && !count($federation)) {
                return [];
            }

            $team = [
                'id' => $teamFederation['id'],
                'created_at' => $teamFederation['created_at'],
                'updated_at' => $teamFederation['updated_at'],
                'deleted_at' => $teamFederation['deleted_at'],
                'name' => $teamFederation['name'],
                'enabled' => $teamFederation['enabled'],
                'allows_messaging' => $teamFederation['allows_messaging'],
                'workflow_enabled' => $teamFederation['workflow_enabled'],
                'access_requests_management' => $teamFederation['access_requests_management'],
                'uses_5_safes' => $teamFederation['uses_5_safes'],
                'is_admin' => $teamFederation['is_admin'],
                'member_of' => $teamFederation['member_of'],
                'contact_point' => $teamFederation['contact_point'],
                'application_form_updated_by' => $teamFederation['application_form_updated_by'],
                'application_form_updated_on' => $teamFederation['application_form_updated_on'],
                'mdm_folder_id' => $teamFederation['mdm_folder_id'],
                'federation' => $federation,
            ];
            $response[] = $team;

            unset($federation);
            unset($team);
        }

        return $response;
    }
}
