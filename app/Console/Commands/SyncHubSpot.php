<?php

namespace App\Console\Commands;

use Config;
use Exception;
use App\Models\User;
use App\Models\Sector;
use Illuminate\Console\Command;
use App\Services\HubspotService;
use Illuminate\Support\Facades\Http;
use App\Http\Enums\UserContactPreference;

class SyncHubspot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-hubspot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync contacts from HubSpot to mk2 users';

    protected $hubspotService;

    public function __construct()
    {
        parent::__construct();
        $this->hubspotService = new HubspotService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $propertiesNew = [
        //     'email' => 'testingapis.123@hubspot.com',
        //     'firstname' => 'Adrian',
        //     'lastname' => 'Mott',
        //     'website' => 'http://hubspot.com',
        //     'company' => 'HubSpot',
        //     'phone' => '555-122-2323',
        //     'address' => '25 First Street',
        //     'city' => 'Cambridge',
        //     'state' => 'MA',
        //     'zip' => '02139',
        // ];
        // $propertiesUpdate = [
        //     'email' => 'testingapis.123.update2@hubspot.com',
        //     'firstname' => 'Updated',
        //     'lastname' => 'Record',
        //     'website' => 'http://updated.example.com',
        //     'lifecyclestage' => 'customer',
        // ];
        // $create = $this->hubspotService->createContact($propertiesNew);
        // dd($create['vid']);
        // dd($this->hubspotService->updateContactById(17727931102, $propertiesUpdate));
        // dd($this->hubspotService->getContactByEmail('hdrteamadmin@gmail.com'));
        // dd($this->hubspotService->getContactById(17727931102));
        // dd($this->hubspotService->deleteContactById(17765839833));

        // enabled users
        $users = User::where(['is_admin' => 0])->get();

        foreach ($users as $user) {
            $sector = Sector::where([
                'id' => $user->sector_id,
                'enabled' => 1,
            ])->first();

            $commPreference = [];
            ($user->contact_feedback) ?? $commPreference[] = UserContactPreference::USER_FEEDBACK->value;
            ($user->contact_news) ?? $commPreference[] = UserContactPreference::USER_NEWS->value;

            $email = trim(strtolower($user->email));

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
            ];

            // update contact preferences
            if ($user->hubspot_id) {
                $this->hubspotService->updateContactById((int) $user->hubspot_id, $hubspot);
            }
            
            // create new contact hubspot and update users table
            if (!$user->hubspot_id){
                // check by email
                $hubspotId = $this->hubspotService->getContactByEmail($email);

                if (!$hubspotId) {
                    $createContact = $this->hubspotService->createContact($hubspot);
                    // dd($createContact);
                    $hubspotId = $createContact['vid'];
                }

                if ($hubspotId) {
                    $this->hubspotService->updateContactById((int) $hubspotId, $hubspot);
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
        $users = User::onlyTrashed()->where(['is_admin' => 1])->get();
        foreach ($users as $user) {
            $hubspot = [
                'gateway_registered_user' => 'No',
            ];

            // update contact if exist
            if ($user->hubspot_id) {
                $this->hubspotService->updateContactById((int) $user->hubspot_id, $hubspot);
            }

            echo 'The disabled user with email ' . $user->email . ' has been updated', PHP_EOL;
        }
    }
}
