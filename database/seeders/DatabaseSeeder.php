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
            LicenseSeeder::class,
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
            DatasetVersionHasDatasetVersionSeeder::class,
            DatasetVersionHasToolSeeder::class,
            ApplicationSeeder::class,
            ApplicationHasPermissionSeeder::class,
            FAIRShareCommandSeeder::class,
            TeamUserHasRoleSeeder::class,
            FederationSeeder::class,
            TeamHasFederationSeeder::class,
            NamedEntitiesSeeder::class,
            DatasetVersionHasNamedEntitiesSeeder::class,
            CohortRequestSeeder::class,
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
            QuestionBankSeeder::class,
            DataAccessApplicationAnswerSeeder::class,
            DataAccessApplicationSeeder::class,
            DataAccessTemplateSeeder::class,
            EnquiryThreadSeeder::class,
            EnquiryMessageSeeder::class,
            CollectionHasToolSeeder::class,
            CategorySeeder::class,
            DurHasPublicationSeeder::class,
            ProgrammingLanguageSeeder::class,
            ProgrammingPackageSeeder::class,
            TypeCategorySeeder::class,
            CollectionHasPublicationSeeder::class,
            PublicationHasToolSeeder::class,
            DataProviderCollsSeeder::class,
            DurHasToolSeeder::class,
            LibrarySeeder::class,
            CohortRequestEmailSeeder::class,
        ]);
    }
}
