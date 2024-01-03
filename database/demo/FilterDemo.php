<?php

namespace Database\Demo;

use Illuminate\Database\Seeder;
use App\Models\AuthorisationCode;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FilterDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $output = new ConsoleOutput();

        $filters = include getcwd() . '/database/demo/files/filters_short.php';
        $url = env('APP_URL') . '/api/v1/filters';
        $authorisation = AuthorisationCode::first();

        $progressBar = new ProgressBar($output, count($filters));
        $progressBar->start();

        foreach ($filters as $key => $filter) {
            $output2 = new ConsoleOutput();
            $progressBar2 = new ProgressBar($output, count($filter));
            $progressBar2->start();

            foreach ($filter as $item) {
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

                $progressBar2->advance();
            }

            $progressBar2->finish();
            $output2->write('   seed type ' . $key . ' Finnished', true);

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->write('   seed FilterDemo Finnished', true);
    }
}
