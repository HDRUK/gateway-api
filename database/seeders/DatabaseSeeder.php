<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\TagSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\FilterSeeder;
use Database\Seeders\DarIntegrationSeeder;
use Database\Seeders\PublisherSeeder;

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
        ]);
    }
}
