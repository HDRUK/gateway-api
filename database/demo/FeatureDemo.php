<?php

namespace Database\Demo;

use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FeatureDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $output = new ConsoleOutput();

        $features = include getcwd() . '/database/demo/files/features_short.php';
        $url = env('APP_URL') . '/api/v1/features';
        $authorisation = AuthorisationCode::first();

        $progressBar = new ProgressBar($output, count($features));
        $progressBar->start();

        foreach ($features as $feature) {
            $payload = [
                'name' => trim($feature),
                'enabled' => true
            ];

            Http::withHeaders([
                'Authorization' => 'Bearer ' . $authorisation->jwt,
                'Content-Type' => 'application/json', // Adjust content type as needed
            ])->post($url, $payload);

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->write('   seed FeaturesDemo Finnished', true);
    }
}
