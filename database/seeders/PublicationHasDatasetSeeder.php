<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\Publication;
use App\Models\PublicationHasDataset;
use Illuminate\Database\Seeder;

class PublicationHasDatasetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 10; $count++) {
            $pubId = Publication::all()->random()->id;
            $datasetId = Dataset::all()->random()->id;
            $type = fake()->randomElement(['ABOUT', 'USING']);

            $pubHasDataset = PublicationHasDataset::where([
                'publication_id' => $pubId,
                'dataset_id' => $datasetId,
                'link_type' => $type,
            ])->first();

            if (!$pubHasDataset) {
                PublicationHasDataset::create([
                    'publication_id' => $pubId,
                    'dataset_id' => $datasetId,
                    'link_type' => $type,
                ]);
            }
        }
    }
}