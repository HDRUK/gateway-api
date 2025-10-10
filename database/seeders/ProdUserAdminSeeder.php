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
        var_dump('begin ProdUserAdminSeeder');

        $this->createUser(
            'HDRUK',
            'Developers',
            'developers@hdruk.ac.uk',
            '$2y$10$rWeNArR4iPMVF6N.Xza/W.pW30W4ABdwxbIEKzaYjZCi3j/Ev9XmS',
            true,
            [
                'hdruk.superadmin',
            ]
        );

        $this->createUser(
            'HDRUK',
            'Service-User',
            'services@hdruk.ac.uk',
            '$2y$10$/4myEGeZmAbAuSEMbt/2be2wmPCPPHfHh055uhycTylAdQ7Aykft6',
            true,
            [
                'hdruk.admin',
                'hdruk.cohort.admin',
            ]
        );
    }
}
