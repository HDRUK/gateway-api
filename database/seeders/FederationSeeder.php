<?php

namespace Database\Seeders;

use App\Models\Federation;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use App\Models\FederationHasNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FederationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Federation::factory(5)->create();

        $countNotifications = Notification::all()->count();

        for ($count = 1; $count <= 5; $count++) {
            Notification::create([
                'notification_type' => 'federation',
                'message' => fake()->words(3, true),
                'opt_in' => fake()->boolean(),
                'enabled' => fake()->boolean(),
                'email' => fake()->unique()->safeEmail(),
            ]);
        }

        for ($count = 1; $count <= 20; $count++) {
            $notificationId = Notification::where('id', '>', $countNotifications)->get()->random()->id;
            $federationId = Federation::all()->random()->id;

            $federationHasNotification = FederationHasNotification::where([
                'notification_id' => $notificationId,
            ])->first();

            if (!$federationHasNotification) {
                FederationHasNotification::create([
                    'federation_id' => $federationId,
                    'notification_id' => $notificationId,
                ]);
            }
        }

        // Add federation simulation server
        $federation = Federation::create([
            'federation_type' => 'dataset',
            'auth_type' => 'api_key',
            'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
            'endpoint_datasets' => '/api/v1/datasets',
            'endpoint_dataset' => '/api/v1/datasets/{id}',
            'run_time_hour' => 0,
            'enabled' => 0,
            'tested' => 1,
        ]);

        $notification = Notification::create([
            'notification_type' => 'federation',
            'message' => 'Simulation server notification',
            'opt_in' => 1,
            'enabled' => 0,
            'email' => fake()->unique()->safeEmail(),
        ]);

        FederationHasNotification::create([
            'federation_id' => $federation->id,
            'notification_id' => $notification->id,
        ]);
    }
}
