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

        // Ensure all tests have at least one active/draft/archived Publication available
        Publication::factory()->create(
            ['status' => Publication::STATUS_ACTIVE]
        );
        Publication::factory()->create(
            ['status' => Publication::STATUS_DRAFT]
        );
        Publication::factory()->create(
            ['status' => Publication::STATUS_ARCHIVED]
        );
    }
}
