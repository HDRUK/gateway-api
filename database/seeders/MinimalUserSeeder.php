<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
            UserAdminsSeeder::class,
            UserSeeder::class,
        ]);
    }
}
