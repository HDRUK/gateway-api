<?php

namespace Database\Seeders;

use App\Models\Publication;

use Illuminate\Database\Seeder;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Publication::factory()->count(10)->create();
    }
}
