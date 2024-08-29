<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            FilterSeeder::class,
            SectorSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            LicenseSeeder::class,
            ActivityLogUserTypeSeeder::class, // ??? Thought this had be superseded by Audit?
            EmailTemplateSeeder::class,
            KeywordSeeder::class,
            SpatialCoverageSeeder::class,
            QuestionBankSeeder::class,
            CategorySeeder::class,
            ProgrammingLanguageSeeder::class,
            ProgrammingPackageSeeder::class,
            TypeCategorySeeder::class,
            CohortRequestEmailSeeder::class,
            ProdUserAdminSeeder::class,
        ]);
    }
}
