<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetVersion;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Tests\Traits\MockExternalApis;

class DatasetVersionSeeder extends Seeder
{
    use MockExternalApis;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ds = Dataset::all();

        $dataToInsert = [];
        foreach ($ds as $d) {
            $dataToInsert[] = [
                'dataset_id' => $d->id,
                'metadata' => json_encode(json_encode($this->getFakeDataset())),
                'version' => fake()->unique()->numberBetween(1, 50),
            ];
        }

        DatasetVersion::insert($dataToInsert);
    }
}