<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use App\Models\UserHasNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserHasNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notificationIds = Notification::all()->pluck('id')->toArray();
        $userIds = User::all()->pluck('id')->toArray();

        $count = 0;
        while ($count < 100) {
            $notificationId = $notificationIds[array_rand($notificationIds)];
            $userId = $userIds[array_rand($userIds)];

            $userHasNotification = UserHasNotification::where([
                'notification_id' => $notificationId,
                'user_id' => $userId,
            ])->first();

            if (!$userHasNotification) {
                UserHasNotification::create([
                    'notification_id' => $notificationId,
                    'user_id' => $userId,
                ]);
                $count += 1;
            }

        }
    }
}
