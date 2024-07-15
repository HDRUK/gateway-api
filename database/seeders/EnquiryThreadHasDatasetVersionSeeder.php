<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EnquiryThreadHasDatasetVersion;

class EnquiryThreadHasDatasetVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Generate 10 records for testing
        EnquiryThreadHasDatasetVersion::factory()->count(10)->create();
    }
}
