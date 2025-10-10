<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's baseline database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            FilterSeeder::class,
            SectorSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            LicenseSeeder::class,
            ActivityLogUserTypeSeeder::class,
            EmailTemplateSeeder::class,
            FAIRShareCommandSeeder::class,
            KeywordSeeder::class,
            SpatialCoverageSeeder::class,
            QuestionBankSeeder::class,
            CategorySeeder::class,
            ProgrammingLanguageSeeder::class,
            ProgrammingPackageSeeder::class,
            TypeCategorySeeder::class,
            CohortRequestEmailSeeder::class,
            UserAdminsSeeder::class,
            MinimalUserSeeder::class,
            TeamHasUserSeeder::class,
            ApplicationSeeder::class,
            CollectionSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            KeywordSeeder::class,
            CategorySeeder::class,
            TypeCategorySeeder::class,
            LicenseSeeder::class,
            ToolSeeder::class,
            TagSeeder::class,
            DurSeeder::class,
            DurHasDatasetVersionSeeder::class,
            CollectionHasKeywordSeeder::class,
            CollectionHasDatasetVersionSeeder::class,
            CollectionHasToolSeeder::class,
            CollectionHasDurSeeder::class,
            PublicationSeeder::class,
            PublicationHasDatasetVersionSeeder::class,
            CollectionHasPublicationSeeder::class,
            CollectionHasUserSeeder::class,
        ]);
    }
}
