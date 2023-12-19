<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class DemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:demo-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demo data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting migration for demo data");

        // $this->info("Running migrate:fresh");
        // Artisan::call('migrate:fresh');
 
        // $this->info("Running seed SectorDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\SectorDemo"]);

        // $this->info("Running seed PermissionDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\PermissionDemo"]);

        // $this->info("Running seed RoleDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\RoleDemo"]);

        // $this->info("Running seed UserStartDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\UserStartDemo"]);

        // $this->info("Running authorisation");
        // $this->createAuthorization();

        // $this->info("Running seed FeatureDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\FeatureDemo"]);

        // $this->info("Running seed FilterDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\FilterDemo"]);

        // $this->info("Running seed UserAddDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\UserAddDemo"]);

        // $this->info("Running seed TeamDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\TeamDemo"]);

        // $this->info("Running seed TeamUserRoleDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\TeamUserRoleDemo"]);

        // $this->info("Running seed CohortRequestDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\CohortRequestDemo"]);

        // $this->info("Running seed ApplicationDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\ApplicationDemo"]);

        // $this->info("Running seed FederationDemo");
        // Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\FederationDemo"]);

        $this->info("Running seed DatasetDemo");
        Artisan::call('db:seed', ['--class' => "Database\Seeders\Demo\DatasetDemo"]);

        $this->info("Completed...");
    }

    public function createAuthorization() 
    {
        $url = env('APP_URL') . '/api/v1/auth';
        $payload = [
            'email' => 'developers@hdruk.ac.uk',
            'password' => 'Watch26Task?', 
        ];

        $response = Http::post($url, $payload);
        // $statusCode = $response->status();
        $responseData = $response->json();

        // $this->info("HTTP Status Code: {$statusCode}");
        // $this->info("Response Data :: " . json_encode($responseData));
        $this->info("Bearer Token :: " . $responseData["access_token"]);
    }
}
