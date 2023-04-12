<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\TagSeeder;
use Database\Seeders\UserSeeder;
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
        \App\Models\Filter::factory(50)->create();
        
        //\App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'firstname' => 'HDRUK',
            'lastname' => 'Super-User',
            'email' => 'developers@hdruk',
            'password' => Hash::make('S0mePass\/\/ord!'),
        ]);

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            TagSeeder::class,
            UserSeeder::class,
        ]);
    }
}
