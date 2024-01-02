<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FederationDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $federations = [
            1 => [
                'federation_type' => 'dataset',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/auth/datasets',
                'endpoint_dataset' => '/api/v1/auth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notifications' => ['wenzlaff@hotmail.com', 'scotfl@outlook.com', 'sinkou@optonline.net'],
                'tested' => true
            ],
            2 => [
                'federation_type' => 'dataset',
                'auth_type' => 'API_KEY',
                'auth_secret_key' => 'YGd50xWiaho6Aopx-Ti9eSCu2tByr7plSgUrNs87ikk',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/apilkey/datasets',
                'endpoint_dataset' => '/api/v1/apilkey/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notifications' => ['wbarker@optonline.net', 'curly@comcast.net', 'weidai@aol.com'],
                'tested' => true
            ],
            3 => [
                'federation_type' => 'dataset',
                'auth_type' => 'NO_AUTH',
                'auth_secret_key' => '',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notifications' => ['notaprguy@yahoo.ca', 'konst@gmail.com', 'offthelip@optonline.net'],
                'tested' => true
            ],
        ];

        $authorisation = AuthorisationCode::first();
        foreach ($federations as $teamId => $federation) {
            $url = env('APP_URL') . '/api/v1/teams/' . $teamId . '/federations';
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $authorisation->jwt,
                'Content-Type' => 'application/json',
            ])->post($url, $federation);
        }
    }
}
