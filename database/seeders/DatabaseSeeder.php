<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
            SectorSeeder::class,
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
            UserHasNotificationSeeder::class,
            EmailTemplatesSeeder::class,
            ReviewSeeder::class,
            CollectionSeerder::class,
            AuditLogSeeder::class,
            DataUseRegisterSeeder::class,
        ]);
    }
}
