<?php

namespace Database\Demo;

use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeamDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $output = new ConsoleOutput();

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
                'application_form_updated_on' => '2023-03-22T08:39:44.646+00:00',
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
                'application_form_updated_on' => '2023-03-22T08:39:44.646+00:00',
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
                'application_form_updated_on' => '2023-03-22T08:39:44.646+00:00',
                'enabled' => 1,
                'mongo_object_id' => '5f8992a97150a1b050be0712',
                'notifications' => [],
            ],
        ];

        $url = env('APP_URL') . '/api/v1/teams';
        $authorisation = AuthorisationCode::first();

        $progressBar = new ProgressBar($output, count($teams));
        $progressBar->start();

        foreach ($teams as $team) {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $authorisation->jwt,
                'Content-Type' => 'application/json',
            ])->post($url, $team);

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->write('   seed TeamDemo Finnished', true);
    }
}
