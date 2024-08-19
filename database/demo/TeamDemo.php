<?php

namespace Database\Demo;

use Exception;

use App\Models\Team;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class TeamDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'name' => 'HEALTH DATA RESEARCH',
                'allows_messaging' => 1,
                'workflow_enabled' =>  1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => 'OTHER',
                'contact_point' => 'nobody',
                'application_form_updated_by' => 'who care',
                'application_form_updated_on' => '2023-03-22 08:39:44',
                'enabled' => 1,
                'mongo_object_id' => '5f7b1a2bce9f65e6ed83e7da',
                'notifications' => [],
            ],
            [
                'name' => 'SAIL',
                'allows_messaging' => 1,
                'workflow_enabled' =>  1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 0,
                'member_of' => 'ALLIANCE',
                'contact_point' => 'nobody',
                'application_form_updated_by' => 'who care',
                'application_form_updated_on' => '2023-03-22 08:39:44',
                'enabled' => 1,
                'mongo_object_id' => '5f3f98068af2ef61552e1d75',
                'notifications' => [],
            ],
            [
                'name' => 'PUBLIC HEALTH SCOTLAND',
                'allows_messaging' => 1,
                'workflow_enabled' =>  1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 0,
                'member_of' => 'ALLIANCE',
                'contact_point' => 'nobody',
                'application_form_updated_by' => 'who care',
                'application_form_updated_on' => '2023-03-22 08:39:44',
                'enabled' => 1,
                'mongo_object_id' => '5f8992a97150a1b050be0712',
                'notifications' => [],
            ],
        ];

        foreach ($teams as $team) {
            try {
                Team::create($team);

            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }
        }
    }
}
