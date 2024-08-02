<?php

namespace Database\Demo;

use Exception;
use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FilterDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filters = include getcwd() . '/database/demo/files/filters_short.php';
        $url = env('APP_URL') . '/api/v1/filters';
        $authorisation = AuthorisationCode::first();

        foreach ($filters as $key => $filter) {
            foreach ($filter as $item) {
                try {
                    $payload = [
                        'type' => $key,
                        'value' => $item,
                        'keys' => $key,
                        'enabled' => 1,
                    ];

                    Http::withHeaders([
                        'Authorization' => 'Bearer ' . $authorisation->jwt,
                        'Content-Type' => 'application/json', // Adjust content type as needed
                    ])->post($url, $payload);
                } catch (Exception $exception) {
                    throw new Exception($exception->getMessage());
                }
            }
        }
    }
}
