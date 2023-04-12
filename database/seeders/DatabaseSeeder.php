<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\TagSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\FilterSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\DarIntegration::factory(50)->create();

        $this->call([
            FilterSeeder::class,
            UserSeeder::class,
            TagSeeder::class,
            FeatureSeeder::class,
        ]);
    }
}
