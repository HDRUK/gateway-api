<?php

namespace Database\Demo;

use Exception;
use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;

class FeatureDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = include getcwd() . '/database/demo/files/features_short.php';
        $url = env('APP_URL') . '/api/v1/features';
        $authorisation = AuthorisationCode::first();

        foreach ($features as $feature) {
            try {
                $payload = [
                    'name' => trim($feature),
                    'enabled' => true
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
