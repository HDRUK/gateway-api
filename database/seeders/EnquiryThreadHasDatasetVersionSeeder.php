<?php

namespace Database\Seeders;

use App\Models\EnquiryThread;
use App\Models\DatasetVersion;
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
        for ($i = 1; $i <= 15; $i++) {
            $enquiryThreadId = EnquiryThread::all()->random()->id;
            $datasetVersionId = DatasetVersion::all()->random()->id;
            $interestType = fake()->randomElement(['PRIMARY', 'SECONDARY']);

            $enquireThreadHasDatasetVersion = EnquiryThreadHasDatasetVersion::where([
                'enquiry_thread_id' => $enquiryThreadId,
                'dataset_version_id' => $interestType,
            ])->first();

            if (is_null($enquireThreadHasDatasetVersion)) {
                EnquiryThreadHasDatasetVersion::create([
                    'enquiry_thread_id' => $enquiryThreadId,
                    'dataset_version_id' => $interestType,
                    'interest_type' => $interestType,
                ]);
            }
        }
    }
}
