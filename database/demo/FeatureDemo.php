<?php

namespace Database\Demo;

use Exception;

use App\Models\Feature;

use Illuminate\Database\Seeder;

class FeatureDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = include getcwd() . '/database/demo/files/features_short.php';

        foreach ($features as $feature) {
            try {
                $payload = [
                    'name' => trim($feature),
                    'enabled' => true
                ];
                Feature::create($payload);

            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }
        }
    }
}
