<?php

namespace Database\Seeders;

use App\Models\User;
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
            $noNotifications = rand(1, 5);

            for ($i = 1; $i <= $noNotifications; $i++) {
                $addUserId = fake()->randomElement([0, 1]);

                $userId = User::all()->random()->id;

                $notification = Notification::create([
                    'notification_type' => 'application',
                    'message' => null,
                    'opt_in' => true,
                    'enabled' => true,
                    'user_id' => $addUserId ? $userId : null,
                    'email' => $addUserId ? null : fake()->unique()->safeEmail(),
                ]);

                ApplicationHasNotification::create([
                    'application_id' => $applicationId,
                    'notification_id' => $notification->id,
                ]);
            }
        }
    }
}
