<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
<<<<<<< HEAD
=======
use Database\Seeders\ToolSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\FilterSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\DarIntegrationSeeder;
use Database\Seeders\ActivityLogSeeder;
use Database\Seeders\ActivityLogTypeSeeder;
use Database\Seeders\ActivityLogUserTypeSeeder;
use Database\Seeders\NotificationSeeder;
use Database\Seeders\ToolHasTagSeeder;
use Database\Seeders\TeamHasNotificationSeeder;
>>>>>>> 19f31b9 (add model, migration, seed for teamHadNotifications table)

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            FilterSeeder::class,
            UserSeeder::class,
            TagSeeder::class,
            FeatureSeeder::class,
            DarIntegrationSeeder::class,
            TeamSeeder::class,
            ToolSeeder::class,
            ToolHasTagSeeder::class,
            // This one we do in order to ensure data is linked for
            // demonstration purposes
            ActivityLogUserTypeSeeder::class,
            ActivityLogTypeSeeder::class,
            ActivityLogSeeder::class,
            PermissionSeeder::class,
            // TeamUserPermissionSeeder::class,
            TeamHasUserSeeder::class,
            TeamUserHasPermissionSeeder::class,
            NotificationSeeder::class,
            TeamHasNotificationSeeder::class,
        ]);
    }
}
