<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\Traits\HelperFunctions;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserAdminsSeeder extends Seeder
{
    use HelperFunctions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create our super user account
        $this->createUser(
            'HDRUK',
            'Super-User',
            'developers@hdruk.ac.uk',
            '$2y$10$nDJEl9kavTm4WFRUup6j6eQ8qwTQg69fcNwRym.zFGgjA8izjYkAu',
            true,
            [
                'hdruk.superadmin',
            ]
        );

        // Create our service layer user account
        // TODO - Need to review permissions for this account overall as superadmin may be too much depending
        // on actual needs
        $this->createUser(
            'HDRUK',
            'Service-User',
            'services@hdruk.ac.uk',
            '$2y$10$qmXzkOCukyMCXwYrSuNgE.S7MMkswr7/vIoENJngxdn5kdeiwCcyu',
            true,
            [
                'hdruk.superadmin'
            ]
        );

        // Create our automation test users
        $this->createUser(
            'HDR',
            'Team-Admin',
            'hdrteamadmin@gmail.com',
            '$2y$10$RFY3WBTVyjpQ11rO9BturOAkq9QtESwGcdgtbKIBdPa0/GPspoV/K',
            false,
            [
                'custodian.team.admin'
            ],
            true
        );
        $this->createUser(
            'HDR',
            'Team-Admin-Two',
            'hdrgatea@gmail.com',
            '$2y$10$ksZlH0vRntymA5FrWe98ZOr55JZBRNmB12n6x4IeZTDo4LWI..puW',
            false,
            [
                'custodian.team.admin'
            ],
            true
        );

        $this->createUser(
            'Dev',
            'Eloper',
            'hdrresearcher@gmail.com',
            '$2y$10$adL/Ez.F7uvScJ61ENd8qu1olwoKy3AKxGxnAKaH8GD6SJP1ixfhK',
            false,
            [
                'developer'
            ]
        );
        $this->createUser(
            'HDR',
            'DarManager',
            'hdrdarmanager@gmail.com',
            '$2y$10$Np3TatXPeXOUupxkPUK19uIU5B0ijZaxLuO3HFTM6abycaKJwWFmS',
            false,
            [
                'hdruk.dar'
            ]
        );

        $this->createUser(
            'Metadata',
            'Manager',
            'hdrmetadatamanager@gmail.com',
            '$2y$10$hWC5papUx0beZk6/r6xMjeIkfW/8GtH33lXKGGzu7i1OQs7Fz1NCi',
            false,
            [
                'custodian.metadata.manager'
            ]
        );

        $this->createUser(
            'Metadata',
            'Editor',
            'hdreditorhdr@gmail.com',
            '$2y$10$A5b/SfvWfd6T1aW5Y3CS3O2lrSQHPfAUzvwQNEuDT9csSDHG5K8Py',
            false,
            [
                'metadata.editor'
            ]
        );

        $this->createUser(
            'DarManager',
            'MetadataManager',
            'darmetadatamanager@gmail.com',
            '$2y$10$6cbLYbuCX9CGxChK6WtXYeAKQJ/MYffe4Jp650B/FpojbXHUn.By6',
            false,
            [
            'custodian.dar.manager',
            'custodian.metadata.manager',
            ]
        );

        $this->createUser(
            'Dar',
            'Reviewer',
            'hdrreviewer@gmail.com',
            '$2y$10$2UwpuD8ProC9PJx41KbSJeajKC70HxRosUp38fmrKMCCY2oxfxjX6',
            false,
            [
                'dar.reviewer'
            ]
        );

        $this->createUser(
            'HDR',
            'Cohort-Admin',
            'hdrcohortadmin@gmail.com',
            '$2y$10$ECryx53uja9dgVYVy7n/auJf4MRqBMRWKZauddQvR2APG625MUwIm',
            false,
            [
                'hdruk.cohort.admin'
            ]
        );

        $this->createUser(
            'HDR',
            'Admin',
            'hdrukadmin@gmail.com',
            '$2y$10$as5OWj6YOfl56kvhbz179eu1eo49bYwxbdgggSbMm.1/XTGcDjluK',
            false,
            [
                'hdruk.admin'
            ]
        );
        var_dump('end UserAdminsSeeder');

    }
}
