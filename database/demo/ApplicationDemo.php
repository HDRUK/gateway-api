<?php

namespace Database\Demo;

use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ApplicationDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applications = [
            [
                'name' => 'Application syncing and fetching diverse medical datasets',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => 'platform for syncing and fetching diverse medical datasets for research and analysis.',
                'team_id' => 1,
                'user_id' => 4,
                'notifications' => ['wenzlaff@hotmail.com', 'scotfl@outlook.com', 'sinkou@optonline.net'],
                'permissions' => [
                    7, // datasets.read
                    12, // enquiries.read
                    15, // dar.read.all
                    23, // workflows.read
                ],
                'enabled' => true,
            ],

            [
                'name' => 'Application syncing diverse medical datasets',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => ' information aggregator that compiles datasets from diverse sources, including medical datasets adn others.',
                'team_id' => 2,
                'user_id' => 8,
                'notifications' => ['wbarker@optonline.net', 'curly@comcast.net', 'weidai@aol.com'],
                'permissions' => [
                    7, // datasets.read
                    15, // dar.read.all
                    23, // workflows.read
                ],
                'enabled' => true,
            ],

            [
                'name' => 'Application for fetch datasets',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => 'fetch specific versions of datasets, ensuring data consistency for research or analysis.',
                'team_id' => 3,
                'user_id' => 12,
                'notifications' => ['notaprguy@yahoo.ca', 'konst@gmail.com', 'offthelip@optonline.net'],
                'permissions' => [
                    7, // datasets.read
                    12, // enquiries.read
                    15, // dar.read.all
                ],
                'enabled' => true,
            ],
        ];

        $authorisation = AuthorisationCode::first();
        foreach ($applications as $application) {
            $url = env('APP_URL') . '/api/v1/applications';
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $authorisation->jwt,
                'Content-Type' => 'application/json',
            ])->post($url, $application);
        }
    }
}
