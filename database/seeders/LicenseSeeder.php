<?php

namespace Database\Seeders;

use App\Models\License;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('license') as $item) {
            License::updateOrCreate(
                [
                    'code' => $item['code'],
                ],
                $item
            );
        }
    }
}
