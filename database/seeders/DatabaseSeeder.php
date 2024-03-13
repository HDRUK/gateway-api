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
            TagSeeder::class,
            FeatureSeeder::class,
            DarIntegrationSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            TeamSeeder::class,
            UserSeeder::class,
            ToolSeeder::class,
            ToolHasTagSeeder::class,
            // This one we do in order to ensure data is linked for
            // demonstration purposes
            ActivityLogUserTypeSeeder::class,
            ActivityLogTypeSeeder::class,
            ActivityLogSeeder::class,
            // TeamUserPermissionSeeder::class,
            TeamHasUserSeeder::class,
            // TeamUserHasPermissionSeeder::class,
            NotificationSeeder::class,
            TeamHasNotificationSeeder::class,
            UserHasNotificationSeeder::class,
            EmailTemplatesSeeder::class,
            ReviewSeeder::class,
            CollectionSeeder::class,
            AuditLogSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            ApplicationSeeder::class,
            ApplicationHasPermissionSeeder::class,
            FAIRShareCommandSeeder::class,
            TeamUserHasRoleSeeder::class,
            FederationSeeder::class,
            TeamHasFederationSeeder::class,
            NamedEntitiesSeeder::class,
            DatasetHasNamedEntitiesSeeder::class,
            CohortRequestSeed::class,
            TeamUserHasNotificationSeeder::class,
            KeywordSeeder::class,
            CollectionHasKeywordSeeder::class,
            CollectionHasDatasetSeeder::class,
            SavedSearchSeeder::class,
            SavedSearchHasFilterSeeder::class,
            DurSeeder::class,
            TeamSeederAddPid::class,
            SpatialCoverageSeeder::class,
            PublicationSeeder::class,
            PublicationHasDatasetSeeder::class,
        ]);
    }
}
