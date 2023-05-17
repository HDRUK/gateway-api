<?php

namespace Database\Seeders;

use Hash;
use App\Models\User;
use App\Models\Sector;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'HDRUK Super-User',
            'firstname' => 'HDRUK',
            'lastname' => 'Super-User',
            'email' => 'developers@hdruk.ac.uk',
            'provider' => 'service',
            'password' => Hash::make('Watch26Task?'),
        ]);

        User::factory(10)->create();
    }
}
