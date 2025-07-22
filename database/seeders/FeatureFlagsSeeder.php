<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureFlag;

class FeatureFlagsSeeder extends Seeder
{
    public function run(): void
    {
        FeatureFlag::updateOrCreate(
            ['key' => 'SDEConciergeServiceEnquiry'],
            ['enabled' => true]
        );

        FeatureFlag::updateOrCreate(
            ['key' => 'Aliases'],
            ['enabled' => true]
        );
    }
}
