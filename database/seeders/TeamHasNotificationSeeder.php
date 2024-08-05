<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Notification;
use App\Models\TeamHasNotification;
use Illuminate\Database\Seeder;

class TeamHasNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notificationIds = Notification::all()->pluck('id')->toArray();
        $teamIds = Team::all()->pluck('id')->toArray();

        $count = 0;
        while ($count < 100) {
            $notificationId = $notificationIds[array_rand($notificationIds)];
            $teamId = $teamIds[array_rand($teamIds)];

            $teamHasNotification = TeamHasNotification::where([
                'notification_id' => $notificationId,
                'team_id' => $teamId,
            ])->first();

            if (!$teamHasNotification) {
                TeamHasNotification::create([
                    'notification_id' => $notificationId,
                    'team_id' => $teamId,
                ]);
                $count += 1;
            }

        }
    }
}
