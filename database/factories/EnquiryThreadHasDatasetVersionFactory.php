<?php

namespace Database\Factories;

use App\Models\EnquiryThreadHasDatasetVersion;
use App\Models\EnquiryThread;
use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnquiryThreadHasDatasetVersionFactory extends Factory
{
    protected $model = EnquiryThreadHasDatasetVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'enquiry_thread_id' => EnquiryThread::all()->random()->id,
            'dataset_version_id' => DatasetVersion::all()->random()->id,
            'interest_type' => $this->faker->randomElement(['PRIMARY', 'SECONDARY']),
        ];
    }
}
