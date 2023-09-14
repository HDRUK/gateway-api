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
        Federation::factory(50)->create();

        $countNotifications = Notification::all()->count();

        for ($count = 1; $count <= 50; $count++) {
            Notification::create([
                'notification_type' => 'federation',
                'message' => fake()->words(3, true),
                'opt_in' => fake()->boolean(),
                'enabled' => fake()->boolean(),
                'email' => fake()->unique()->safeEmail(),
            ]);
        }

        for ($count = 1; $count <= 200; $count++) {
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
    }
}
