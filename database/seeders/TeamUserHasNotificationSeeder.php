<?php

namespace Database\Seeders;

use App\Models\TeamHasUser;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use App\Models\TeamUserHasNotification;

class TeamUserHasNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teamHasUsers = TeamHasUser::all();

        foreach ($teamHasUsers as $item) {
            $teamHasUserId = $item->id;

            $notification = Notification::create([
                'notification_type' => 'team_user_notification',
                'message' => fake()->words(3, true),
                'opt_in' => fake()->boolean(),
                'enabled' => fake()->boolean(),
                'email' => null,
            ]);

            TeamUserHasNotification::create([
                'team_has_user_id' => $teamHasUserId,
                'notification_id' => $notification->id,
            ]);
        }
    }
}
