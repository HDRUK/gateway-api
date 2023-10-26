<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MinimalUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This Seeder bundles together the minimal seeders required by any tests that make use of Users.
     */
    public function run(): void
    {
        $this->call([
            TeamSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class, 
            UserSeeder::class,
        ]);
    }
}