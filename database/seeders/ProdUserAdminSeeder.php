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
            env('HDR_ADMIN_USER'),
            env('HDR_ADMIN_DB_PASSWORD'),
            true,
            [
                'hdruk.superadmin',
            ]
        );

        $this->createUser(
            'HDRUK',
            'Service-User',
            env('HDR_SERVICE_USER'),
            env('HDR_SERVICE_DB_PASSWORD'),
            true,
            [
                'hdruk.admin',
                'hdruk.cohort.admin',
            ]
        );
    }
}
