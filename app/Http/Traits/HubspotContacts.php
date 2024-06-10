<?php

namespace App\Http\Traits;

use App\Models\User;
use App\Models\Sector;
use App\Services\Hubspot;
use App\Models\CohortRequest;
use App\Http\Enums\UserContactPreference;

trait HubspotContacts
{
    public function updateOrCreateContact(int $id)
    {
        $hubspotService = new Hubspot();
        
        $user = User::where('id', $id)->first();

        if ($user) {
            $sector = Sector::where([
                'id' => $user->sector_id,
                'enabled' => 1,
            ])->first();
    
            $commPreference = [];
            ($user->contact_feedback) ?? $commPreference[] = UserContactPreference::USER_FEEDBACK->value;
            ($user->contact_news) ?? $commPreference[] = UserContactPreference::USER_NEWS->value;
    
            $email = trim(strtolower($user->email));
    
            $cohortRequest = CohortRequest::where([
                'user_id' => $user->id,
                'request_status' => 'APPROVED',
            ])->first();
    
            $hubspot = [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $email,
                'orcid_number' => $user->orcid ? preg_replace('/[^0-9]/', '', $user->orcid) : '',
                'related_organisation_sector' => $sector ? $sector->name : '',
                'company' => $user->organisation,
                'communication_preference' => count($commPreference) ? implode(";", $commPreference) : '',
                'gateway_registered_user' => 'Yes',
                'gateway_roles' => 'User',
                'cohort_registered_user' => $cohortRequest ? 'Yes' : 'No',
            ];
    
            // update contact preferences
            if ($user->hubspot_id) {
                $hubspotService->updateContactById((int) $user->hubspot_id, $hubspot);
            }
            
            // create new contact hubspot and update users table
            if (!$user->hubspot_id){
                // check by email
                $hubspotId = $hubspotService->getContactByEmail($email);
                if (!$hubspotId) {
                    $createContact = $hubspotService->createContact($hubspot);
                    $hubspotId = (is_array($createContact) && array_key_exists('vid', $createContact)) ? $createContact['vid'] : null;
                }
    
                if ($hubspotId) {
                    $hubspotService->updateContactById((int) $hubspotId, $hubspot);
                }

                User::where([
                    'id' => $user->id,
                ])->update([
                    'hubspot_id' => $hubspotId
                ]);
            }
        } else {
            $user = User::withTrashed()->where('id', $id)->first();

            $hubspot = [
                'gateway_registered_user' => 'No',
            ];

            // update contact if exist
            if ($user->hubspot_id) {
                $hubspotService->updateContactById((int) $user->hubspot_id, $hubspot);
            }
        }
    }
}
