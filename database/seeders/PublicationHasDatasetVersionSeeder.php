<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Database\Seeder;

class PublicationHasDatasetVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 10; $count++) {
            $pubId = Publication::all()->random()->id;
            $datasetVersionId = Dataset::all()->random()->latestVersion()->id;
            $type = fake()->randomElement(['ABOUT', 'USING']);

            $pubHasDataset = PublicationHasDatasetVersion::where([
                'publication_id' => $pubId,
                'dataset_version_id' => $datasetVersionId,
                'link_type' => $type,
            ])->first();

            if (!$pubHasDataset) {
                PublicationHasDatasetVersion::create([
                    'publication_id' => $pubId,
                    'dataset_version_id' => $datasetVersionId,
                    'link_type' => $type,
                ]);
            }
        }
    }
}
