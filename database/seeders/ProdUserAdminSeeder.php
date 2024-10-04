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
            '$2y$10$3raHZy7qdxNQ7YcyxPn8bO/01RrtZxlRXfph/mkCk7XSyqZmOc36.',
            true,
            [
                'hdruk.superadmin',
            ]
        );

        $this->createUser(
            'HDRUK',
            'Service-User',
            'services@hdruk.ac.uk',
            '$2y$10$TOtlDGGFnNUbemk2dgBLQOwnThzLSKGOcaWC5zmCFDrIheMeQTFO.',
            true,
            [
                'hdruk.admin',
                'hdruk.cohort.admin',
            ]
        );
    }
}
