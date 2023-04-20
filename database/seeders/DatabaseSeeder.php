<?php

namespace Database\Seeders;

use Database\Seeders\TagSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\ToolSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\FilterSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\DarIntegrationSeeder;
use Database\Seeders\ActivityLogSeeder;
use Database\Seeders\ActivityLogTypeSeeder;
use Database\Seeders\ActivityLogUserTypeSeeder;

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
        ]);
    }
}
