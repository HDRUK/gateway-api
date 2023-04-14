<?php

namespace Database\Seeders;

use Database\Seeders\TagSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\ToolSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\FilterSeeder;
use Database\Seeders\PublisherSeeder;
use Database\Seeders\DarIntegrationSeeder;

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
            PublisherSeeder::class,
            ToolSeeder::class,
            ToolHasTagSeeder::class,
        ]);
    }
}
