<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Database\Seeders\Traits\HelperFunctions;

class ProdUserAdminSeeder extends Seeder
{
    use HelperFunctions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createUser(
            'HDRUK',
            'Developers',
            'developers@hdruk.ac.uk',
            '$2y$10$nDJEl9kavTm4WFRUup6j6eQ8qwTQg69fcNwRym.zFGgjA8izjYkAu',
            true,
            [
                'hdruk.superadmin',
            ]
        );

        $this->createUser(
            'HDRUK',
            'Service-User',
            'services@hdruk.ac.uk',
            '$2y$10$qmXzkOCukyMCXwYrSuNgE.S7MMkswr7/vIoENJngxdn5kdeiwCcyu',
            true,
            [
                'hdruk.admin',
                'hdruk.cohort.admin',
            ]
        );
    }
}
