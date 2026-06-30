<?php

namespace Database\Seeders;

use App\Models\Dataset;
use Illuminate\Database\Seeder;

class CRUKDatasetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Dataset::factory()->count(20)->create([
            'partner_context' => 'CRUK',
            'status' => Dataset::STATUS_ACTIVE,
        ]);
    }
}
