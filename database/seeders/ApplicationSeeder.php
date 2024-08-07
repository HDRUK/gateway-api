<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use App\Models\ApplicationHasNotification;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Application::factory(10)->create();

        $applications = Application::all();

        foreach ($applications as $application) {

            $applicationId = $application->id;
            $noNotifications = rand(1, 3);

            for ($i = 1; $i <= $noNotifications; $i++) {
                $notification = Notification::create([
                    'notification_type' => 'application',
                    'message' => null,
                    'opt_in' => true,
                    'enabled' => true,
                    'email' => fake()->unique()->safeEmail(),
                ]);

                ApplicationHasNotification::create([
                    'application_id' => $applicationId,
                    'notification_id' => $notification->id,
                ]);
            }
        }
    }
}
