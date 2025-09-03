<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\HelperFunctions;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CohortServiceUserSeeder extends Seeder
{
    use HelperFunctions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createUser(
            'HDRUK',
            'Cohort-Service-User',
            'cohort-service@hdruk.ac.uk',
            '$2y$10$qmXzkOCukyMCXwYrSuNgE.S7MMkswr7/vIoENJngxdn5kdeiwCcyu',
            true,
            [
                'hdruk.superadmin'
            ]
        );
    }
}
