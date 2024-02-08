<?php

namespace Database\Beta;

use Exception;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamHasUser;
use App\Models\UserHasRole;
use App\Models\TeamUserHasRole;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserBetaDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create our super user account

        $users = [
            [
                'firstname' => 'HDRUK',
                'lastname' => 'Super-User',
                'email' => 'developers@hdruk.ac.uk',
                'password' => '$2y$10$ahiNU4R3ojWyeih8aoKklOxYyGiThhb8X4FXZvVEV0tZbxJhitcXi',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['hdruk.superadmin'],
                'assignTeam' => false,
                'teamName' => '',
            ],
            [
                'firstname' => 'HDRUK',
                'lastname' => 'Super-User',
                'email' => 'services@hdruk.ac.uk',
                'password' => '$2y$10$Uu.OcGmP6DynbnaoFP9uh.WBQ3a9d1J3h6J/P2W.ZJv1xc5DvtZT2',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['hdruk.superadmin'],
                'assignTeam' => false,
                'teamName' => '',
            ],

            [
                'firstname' => 'Simon',
                'lastname' => 'Thompson',
                'email' => 'simon@chi.swan.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'SAIL',
            ],
            [
                'firstname' => 'James',
                'lastname' => 'Friel',
                'email' => 'jfriel001@dundee.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'HIC',
            ],
            [
                'firstname' => 'Adam',
                'lastname' => 'Milward',
                'email' => 'adam@metadataworks.co.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'MetadataWorks',
            ],
            [
                'firstname' => 'David',
                'lastname' => 'Milward',
                'email' => 'david@metadataworks.co.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'MetadataWorks',
            ],
            [
                'firstname' => 'Laura',
                'lastname' => 'Sato',
                'email' => 'laura.sato@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'NHS England',
            ],
            [
                'firstname' => 'Susan',
                'lastname' => 'Hodgson',
                'email' => 'Susan.Hodgson@mhra.gov.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'CPRD',
            ],
            [
                'firstname' => 'Jennifer',
                'lastname' => 'Campbell',
                'email' => 'Jennifer.Campbell@mhra.gov.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'CPRD',
            ],
            [
                'firstname' => 'Chris',
                'lastname' => 'Jaggs',
                'email' => 'Christopher.Jaggs@mhra.gov.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'CPRD',
            ],
            [
                'firstname' => 'Dipesh',
                'lastname' => 'Patel',
                'email' => 'Dipesh.Patel@mhra.gov.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'CPRD',
            ],
            [
                'firstname' => 'Kavitha',
                'lastname' => 'Saravanakumar',
                'email' => 'kavitha.saravanakumar@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'DISCOVER NOW',
            ],
            [
                'firstname' => 'Taryn',
                'lastname' => 'Aspeling',
                'email' => 't.aspeling@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'DISCOVER NOW',
            ],
            [
                'firstname' => 'Piri',
                'lastname' => 'Siva',
                'email' => 'piriyanthi.sivarajah@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'DISCOVER NOW',
            ],
            [
                'firstname' => 'Jon',
                'lastname' => 'Johnson',
                'email' => 'jon.johnson@ucl.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'CLOSER',
            ],
            [
                'firstname' => 'Hayley',
                'lastname' => 'Mills',
                'email' => 'h.mills@ucl.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'CLOSER',
            ],
            [
                'firstname' => 'Malcolm',
                'lastname' => 'Mundy',
                'email' => 'Malc@IONA.BLUE',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Wessex SDE',
            ],
            [
                'firstname' => 'Chris',
                'lastname' => 'Taylor',
                'email' => 'c.m.b.taylor@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'North East and North Cumbria SDE',
            ],
            [
                'firstname' => 'Adam',
                'lastname' => 'Squire',
                'email' => 'a.squire@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'North East and North Cumbria SDE',
            ],
            [
                'firstname' => 'Helen',
                'lastname' => 'Duckworth',
                'email' => 'helen.duckworth@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'North West SDE',
            ],
            [
                'firstname' => 'Laura',
                'lastname' => 'Huynh',
                'email' => 'Laura.Huynh@cancer.org.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Cancer Research Horizons',
            ],
            [
                'firstname' => 'Kinga',
                'lastname' => 'Varnai',
                'email' => 'Kinga.Varnai@ouh.nhs.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Thames Valley and Surrey SDE',
            ],
            [
                'firstname' => 'Ian',
                'lastname' => 'Robb',
                'email' => 'ian.robb@aridhia.com',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Aridhia',
            ],
            [
                'firstname' => 'Eoghan',
                'lastname' => 'Forde',
                'email' => 'eoghan.forde@aridhia.com',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Aridhia',
            ],
            [
                'firstname' => 'Alicia',
                'lastname' => 'Gibson',
                'email' => 'alicia.gibson@aridhia.com',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Aridhia',
            ],
            [
                'firstname' => 'Mike',
                'lastname' => 'Harding',
                'email' => 'm.harding@lancaster.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],
            [
                'firstname' => 'David',
                'lastname' => 'Skelland',
                'email' => 'david.skelland@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],
            [
                'firstname' => 'Olly',
                'lastname' => 'Butters',
                'email' => 'olly.butters@liverpool.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],
            [
                'firstname' => 'Jonny',
                'lastname' => 'Rylands',
                'email' => 'jonny.rylands1@nhs.net',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],
            [
                'firstname' => 'Vishnu',
                'lastname' => 'Chandrabalan',
                'email' => 'vishnu.chandrabalan@lthtr.nhs.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],

            // Dev Testing
            [
                'firstname' => 'Ping',
                'lastname' => 'Yu',
                'email' => 'Ping.Yu@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Dan',
                'lastname' => 'Nita',
                'email' => 'Dan.Nita@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Stephen',
                'lastname' => 'Lavenberg',
                'email' => 'Stephen.Lavenberg@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.metadata.manager', 'custodian.dar.manager'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Isaac',
                'lastname' => 'Odiase',
                'email' => 'Isaac.Odiase@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin', 'custodian.metadata.manager', 'custodian.dar.manager'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Branwen',
                'lastname' => 'Snelling',
                'email' => 'Branwen.Snelling@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Kymme',
                'lastname' => 'Hayley',
                'email' => 'Kymme.Hayley@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['metadata_editor', 'dar.reviewer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Loki',
                'lastname' => 'Sinclair',
                'email' => 'Loki.Sinclair@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['metadata_editor', 'dar.reviewer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Clara',
                'lastname' => 'Fennessy',
                'email' => 'Clara.Fennessy@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin', 'custodian.metadata.manager', 'developer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Damon',
                'lastname' => 'Chow',
                'email' => 'Damon.Chow@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.metadata.manager', 'custodian.dar.manager'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Yemi',
                'lastname' => 'Aiyeola',
                'email' => 'Yemi.Aiyeola@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['metadata_editor', 'dar.reviewer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Chris',
                'lastname' => 'Milner',
                'email' => 'Chris.Milner@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Phil',
                'lastname' => 'Reeks',
                'email' => 'Phil.Reeks@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.metadata.manager', 'custodian.dar.manager'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Sorin',
                'lastname' => 'Gumeni',
                'email' => 'Sorin.Gumeni@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Chandra',
                'lastname' => 'Chintakindi',
                'email' => 'Chandra.Chintakindi@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['metadata_editor', 'dar.reviewer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Calum',
                'lastname' => 'Macdonald',
                'email' => 'calum.macdonald@hdruk.ac.uk',
                'password' => '$2y$10$JbgO1oSSA6QKk4pPmgZDX.r5MxwkbQ/2LkqyG9S2sQa9UGC14BIii',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],

        ];

        foreach ($users as $user) {
            $this->createUser(
                $user['firstname'],
                $user['lastname'],
                $user['email'],
                $user['password'],
                $user['provider'],
                $user['isAdmin'],
                $user['roles'],
                $user['assignTeam'],
                $user['teamName']
            );
        }

    }

    /**
     * Generically creates users per passed params
     * 
     * @param string $firstname     The firstname of the user to create
     * @param string $lastname      The lastname of the user to create
     * @param string $email         The email address of the user to create
     * @param string $password      The password of the user to create
     * @param string $provider      The provider of the user to create
     * @param bool $isAdmin         Whether this user being created is an admin
     * @param array $roles          The roles that should be applied to the user being created
     * @param bool $assignTeam      if assigned to a team
     * @param string $teamName      Team name
     * 
     * @return void
     */
    private function createUser(
        string $firstname,
        string $lastname,
        string $email,
        string $password,
        string $provider,
        bool $isAdmin,
        array $roles,
        bool $assignTeam = false,
        string $teamName = null
    ): void {
        try {
            $user = User::factory()->create([
                'name' => $firstname . ' ' . $lastname,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'provider' => $provider,
                'password' => $password,
                'is_admin' => $isAdmin,
                'preferred_email' => 'primary',
                'secondary_email' => NULL,
                'organisation' => NULL,
                'bio' => NULL,
                'domain' => NULL,
                'link' => NULL,
                'orcid' => NULL,
            ]);

            if ($assignTeam) {
                $teamName = Team::where(['name' => $teamName])->first()->id;

                $thuId = TeamHasUser::create([
                    'team_id' => $teamName,
                    'user_id' => $user->id,
                ]);

                foreach ($roles as $role) {
                    $r = Role::where('name', $role)->first();

                    TeamUserHasRole::create([
                        'team_has_user_id' => $thuId->id,
                        'role_id' => $r->id,
                    ]);
                }
            } else {
                foreach ($roles as $role) {
                    $r = Role::where('name', $role)->first();
                    UserHasRole::create([
                        'user_id' => $user->id,
                        'role_id' => $r->id,
                    ]);
                }
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
