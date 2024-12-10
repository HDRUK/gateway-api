<?php

namespace App\Console\Commands;

use CloudLogger;
use Config;
use App\Models\Role;
use App\Models\User;
use App\Models\Sector;
use App\Services\Hubspot;
use App\Models\TeamHasUser;
use App\Models\CohortRequest;
use App\Models\TeamUserHasRole;
use Illuminate\Console\Command;
use App\Http\Enums\UserContactPreference;

class SyncHubspotContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-hubspot-contacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync contacts from HubSpot to mk2 users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $notInProviderIds = ['service', 'linkedin', 'google'];

        $hubspotEnabled = Config::get('services.hubspot.enabled');

        if (!$hubspotEnabled) {
            echo 'Sync contacts from HubSpot to mk2 users not enabled - please check HUBSPOT_ENABLED in environment', PHP_EOL;
        }

        $hubspotService = new Hubspot();

        // enabled users
        $users = User::where('is_admin', 0)->where('id', '>', 2512)->get();

        foreach ($users as $user) {
            $sector = Sector::where([
                'id' => $user->sector_id,
                'enabled' => 1,
            ])->first();

            $commPreference = [];
            ($user->contact_feedback) ?? $commPreference[] = UserContactPreference::USER_FEEDBACK->value;
            ($user->contact_news) ?? $commPreference[] = UserContactPreference::USER_NEWS->value;

            $email = ($user->provider === 'open-athens' || $user->preferred_email === 'secondary') ? $user->secondary_email : $user->email;
            $email = trim(strtolower($email));

            if (!$email) {
                CloudLogger::write([
                    'action_type' => 'NOTIFY',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'error :: email is null',
                    'user' => $user,
                ]);
                continue;
            }

            $cohortRequest = CohortRequest::where([
                'user_id' => $user->id,
                'request_status' => 'APPROVED',
            ])->first();

            $rolesFullNamesByUserId = $this->getUserRoleNames($user->id);

            $hubspot = [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $email,
                'orcid_number' => $user->orcid ? preg_replace('/[^0-9]/', '', $user->orcid) : '',
                'related_organisation_sector' => $sector ? $sector->name : '',
                'company' => $user->organisation,
                'communication_preference' => count($commPreference) ? implode(';', $commPreference) : '',
                'gateway_registered_user' => 'Yes',
                'gateway_roles' => 'User' . ($rolesFullNamesByUserId ? ';' . implode(';', $rolesFullNamesByUserId) : ''),
                'cohort_registered_user' => $cohortRequest ? 'Yes' : 'No',
            ];

            // update contact preferences
            if ($user->hubspot_id) {
                $hubspotService->updateContactById((int) $user->hubspot_id, $hubspot);
            }

            // create new contact hubspot and update users table
            if (!$user->hubspot_id) {
                // check by email
                $hubspotId = $hubspotService->getContactByEmail($email);

                if (!$hubspotId) {
                    $createContact = $hubspotService->createContact($hubspot);

                    if (array_key_exists('status', $createContact) && $createContact['status'] === 'error') {
                        $this->error('User :: ' . json_encode($hubspot));
                        $this->error('Response :: ' . json_encode($createContact));
                        CloudLogger::write([
                            'action_type' => 'NOTIFY',
                            'action_name' => class_basename($this) . '@' . __FUNCTION__,
                            'description' => 'error',
                            'user' => $hubspot,
                            'hubspot_error' => $createContact,
                        ]);
                        continue;
                    }

                    $hubspotId = $createContact['vid'];
                }

                if ($hubspotId) {
                    $updateContactByIdx = $hubspotService->updateContactById((int) $hubspotId, $hubspot);
                }

                User::where([
                    'id' => $user->id,
                ])->update([
                    'hubspot_id' => $hubspotId
                ]);
            }

            echo 'The user with email ' . $user->email . ' has been created/updated', PHP_EOL;
        }

        // disabled users
        $users = User::onlyTrashed()->where(['is_admin' => 0])->get();
        foreach ($users as $user) {
            $hubspot = [
                'gateway_registered_user' => 'No',
            ];

            // update contact if exist
            if ($user->hubspot_id) {
                $hubspotService->updateContactById((int) $user->hubspot_id, $hubspot);
            }

            echo 'The disabled user with email ' . $user->email . ' has been updated', PHP_EOL;
        }
    }

    private function getUserRoleNames(int $userId): array
    {
        $teamUserIds = TeamHasUser::where([
            'user_id' => $userId,
        ])->pluck('id');

        if (!count($teamUserIds)) {
            return [];
        }

        $roleIds = TeamUserHasRole::whereIn('team_has_user_id', $teamUserIds)->pluck('role_id')->toArray();
        $roleNames = Role::whereNotNull('full_name')->whereIn('id', $roleIds)->pluck('full_name')->toArray();

        return $roleNames;
    }
}
