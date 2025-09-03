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
            // BaseDatabaseSeeder::class,
            // TagSeeder::class,
            // FeatureSeeder::class,
            // DarIntegrationSeeder::class,
            // TeamSeeder::class,
            // UserSeeder::class,
            // ToolSeeder::class,
            // ToolHasTagSeeder::class,
            // // This one we do in order to ensure data is linked for
            // // demonstration purposes
            // ActivityLogTypeSeeder::class,
            // ActivityLogSeeder::class,
            // // TeamUserPermissionSeeder::class,
            // TeamHasUserSeeder::class,
            // // TeamUserHasPermissionSeeder::class,
            // NotificationSeeder::class,
            // TeamHasNotificationSeeder::class,
            // UserHasNotificationSeeder::class,
            // ReviewSeeder::class,
            // CollectionSeeder::class,
            // CollectionHasUserSeeder::class,
            // AuditLogSeeder::class,
            // DatasetSeeder::class,
            // DatasetVersionSeeder::class,
            // DatasetVersionHasDatasetVersionSeeder::class,
            // DatasetVersionHasToolSeeder::class,
            // ApplicationSeeder::class,
            // ApplicationHasPermissionSeeder::class,
            // TeamUserHasRoleSeeder::class,
            // FederationSeeder::class,
            // TeamHasFederationSeeder::class,
            // NamedEntitiesSeeder::class,
            // DatasetVersionHasNamedEntitiesSeeder::class,
            // CohortRequestSeeder::class,
            // TeamUserHasNotificationSeeder::class,
            // CollectionHasKeywordSeeder::class,
            // CollectionHasDatasetVersionSeeder::class,
            // SavedSearchSeeder::class,
            // SavedSearchHasFilterSeeder::class,
            // DurSeeder::class,
            // TeamSeederAddPid::class,
            // PublicationSeeder::class,
            // PublicationHasDatasetVersionSeeder::class,
            // DataAccessApplicationAnswerSeeder::class,
            // DataAccessApplicationSeeder::class,
            // DataAccessTemplateSeeder::class,
            EnquiryThreadSeeder::class,
            EnquiryMessageSeeder::class,
            CollectionHasToolSeeder::class,
            DurHasPublicationSeeder::class,
            CollectionHasPublicationSeeder::class,
            PublicationHasToolSeeder::class,
            DataProviderCollsSeeder::class,
            DurHasToolSeeder::class,
            DurHasDatasetVersionSeeder::class,
            LibrarySeeder::class,
            EnquiryThreadHasDatasetVersionSeeder::class,
            DatasetVersionHasSpatialCoverageSeeder::class,
        ]);
    }
}
