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

class UserBetaDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create our super user account
        var_dump('begin UserBetaDemo (seeder)');
        $users = [
            [
                'firstname' => 'HDRUK',
                'lastname' => 'Super-User',
                'email' => 'developers@hdruk.ac.uk',
                'password' => '$2y$10$57QFbrNCa3kNg1.DyFBhoO9GMV1NtahzJ7zSXixPil1dBKKctQIR.',
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
                'password' => '$2y$10$AzdP7ITYj3GkQrk4xXi9KOp2VZTO4QwjxUIm1MjAD2T6FnTNcM09e',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['hdruk.superadmin'],
                'assignTeam' => false,
                'teamName' => '',
            ],

            // SAIL
            [
                'firstname' => 'Simon',
                'lastname' => 'Thompson',
                'email' => 'simon@chi.swan.ac.uk',
                'password' => '$2y$10$Igk.NkGnh4mh8sLhPYLVUeMmGoKgj33YboceFm3iWNEJ.Y1YuXL7m',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'SAIL',
            ],

            // HIC
            [
                'firstname' => 'James',
                'lastname' => 'Friel',
                'email' => 'jfriel001@dundee.ac.uk',
                'password' => '$2y$10$wKSxGbtqhBz5elfM23GBaOGz03IH9q7d8Y44XPDq6bP0d5hQQuf7m',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'HIC',
            ],

            // MetadataWorks
            [
                'firstname' => 'Adam',
                'lastname' => 'Milward',
                'email' => 'adam@metadataworks.co.uk',
                'password' => '$2y$10$VvB/h3sF2ploaRxSiTf6XuiJV/70I..mJ4OnAuyJ3MMl2efP66lmS',
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
                'password' => '$2y$10$2vOL4zKcXUp5Eebogp3MN.2i5Obx9VcHCb6B0J/0KB0UFnmy5TlFO',
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
                'password' => '$2y$10$q/cLb4XicTcqRgotSPTsGuOHCdIHbNdA6HpEFFhFWrxqfWtovqb/u',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'NHS England',
            ],

            // CPRD
            [
                'firstname' => 'Susan',
                'lastname' => 'Hodgson',
                'email' => 'Susan.Hodgson@mhra.gov.uk',
                'password' => '$2y$10$/.UJlGNpRwo/kRUAwbmUS.I25lVzWjgutyljZnYCBhbJ7898PvyoW',
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
                'password' => '$2y$10$scM0y4W8GpsY/KKqIXU06OdfgScbpAtLcYDL7WxUQmOiX.w.d/qNa',
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
                'password' => '$2y$10$aTuzbcPnoVhTuf8id9Z4MOR/dD3Ju53tQFDB/2NaKJu5Cp84PZez.',
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
                'password' => '$2y$10$xs2e7D6bVXWRDn8XHQvWt.s7zsYZf7Q6xV/23lq1D09OA7PdOVzX2',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'CPRD',
            ],

            // DISCOVER NOW
            [
                'firstname' => 'Kavitha',
                'lastname' => 'Saravanakumar',
                'email' => 'kavitha.saravanakumar@nhs.net',
                'password' => '$2y$10$gOU.MRZN/Dt09eiNFgbYzO7hGxO3AFPfIf/hI/aqI8XL7O5JIaKyW',
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
                'password' => '$2y$10$W6jOaXIXEuVdN0964JROKu8uL/davuarSYkemFaR65ZG5DLeUl/TW',
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
                'password' => '$2y$10$PZ6jnZvlRwwEs.N51dRS5OyxX.hiejs0Bv0j37TvxUSIRittk1HHK',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'DISCOVER NOW',
            ],
            [
                'firstname' => 'Manohar',
                'lastname' => 'Pattem',
                'email' => 'm.pattem@nhs.net',
                'password' => '$2y$10$w.zAyYG6fmwUx9wv3cXmlOqbZIYX5KSugU3oAXrd2tlsx8tkDqwyC',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'DISCOVER NOW',
            ],

            // CLOSER
            [
                'firstname' => 'Jon',
                'lastname' => 'Johnson',
                'email' => 'jon.johnson@ucl.ac.uk',
                'password' => '$2y$10$JdOU/fXK9DK1L5qGzcnXm.L96lWBA8nPKidSU5JTrlOgV/kthBReO',
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
                'password' => '$2y$10$9p94WxQnJPiZqlHYJn53w.hopy.jDaZ.fd1e2l0B2.AYIHQioAf32',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'CLOSER',
            ],

            // Wessex SDE
            [
                'firstname' => 'Malcolm',
                'lastname' => 'Mundy',
                'email' => 'Malc@IONA.BLUE',
                'password' => '$2y$10$Z3GrtbEgj19tURuoiLFEie9xq9u1M2Nh5mo2jHYna6nl8cd6MxA9a',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Wessex SDE',
            ],
            [
                'firstname' => 'Jo',
                'lastname' => 'Musgrove',
                'email' => 'j.musgrove@soton.ac.uk',
                'password' => '$2y$10$apeLKOL2K0r3XrKz68EKde2ceDqfaSh34xHSB.8D5IyVY4UkAcYdK',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Wessex SDE',
            ],
            [
                'firstname' => 'Ashley',
                'lastname' => 'Heinson',
                'email' => 'a.heinson@soton.ac.uk',
                'password' => '$2y$10$J4u78LGXacNMv.fQcG4sIOhA4pI.Dk0BCuC7iX/dWNAK1QtNo2SKy',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Wessex SDE',
            ],

            // North East and North Cumbria SDE
            [
                'firstname' => 'Chris',
                'lastname' => 'Taylor',
                'email' => 'c.m.b.taylor@nhs.net',
                'password' => '$2y$10$RmS0gPxpz32OqGAIqO.1qOhHRkrWcLQQDDcTaMMtTgM0r0bZPUvd.',
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
                'password' => '$2y$10$TeWs6ntsF2f8/2t2gjsR8uP9PRBqex9Bvj0zgIfyDV0SuTDUDIQNS',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'North East and North Cumbria SDE',
            ],

            // North West SDE
            [
                'firstname' => 'Helen',
                'lastname' => 'Duckworth',
                'email' => 'helen.duckworth@nhs.net',
                'password' => '$2y$10$VPn3JkEBj4aH1fb1gkNN5.HH.lZ3YV2.eMQhq7kftd7MYXBIU7jCG',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'North West SDE',
            ],

            // Cancer Research Horizons
            [
                'firstname' => 'Laura',
                'lastname' => 'Huynh',
                'email' => 'Laura.Huynh@cancer.org.uk',
                'password' => '$2y$10$9XbC5151qIpPDExgtUHlu.ScqidQFS72y6naUCN0xaFwNYhF60CT2',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Cancer Research Horizons',
            ],

            // Thames Valley and Surrey SDE
            [
                'firstname' => 'Kinga',
                'lastname' => 'Varnai',
                'email' => 'Kinga.Varnai@ouh.nhs.uk',
                'password' => '$2y$10$VteAtDi8l3Mx2lW19RO4heZrePhgWIY0eCBZGOfE.PKP.0MTwoKIu',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Thames Valley and Surrey SDE',
            ],

            // Aridhia
            [
                'firstname' => 'Ian',
                'lastname' => 'Robb',
                'email' => 'ian.robb@aridhia.com',
                'password' => '$2y$10$M/lkcWk84vhB4S0J/eKu9OoSP7oDilzoBMzN8owM5GL68hyyCuOXq',
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
                'password' => '$2y$10$eXxuVPvNvz9QzHUxQC/7Mu9oUFIq9zALdQCknm.H0ADJwdb43jkBe',
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
                'password' => '$2y$10$/lEnCoWjTrVZLw.Z80AyluWFtjfmjjuHnrifQRocW4IrBPhEE9nfe',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Aridhia',
            ],

            // Northwest SDE
            [
                'firstname' => 'Mike',
                'lastname' => 'Harding',
                'email' => 'm.harding@lancaster.ac.uk',
                'password' => '$2y$10$a5gYHp42eiODEEOqH7lVcewA16VeAhPpZq4GSBOaFsJmJcXavih.6',
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
                'password' => '$2y$10$J1kWBxboAZ23G4nrpEcm7e9OrgEPOjk0zKqaNs2iG5I3yWM5vRVfC',
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
                'password' => '$2y$10$w8PHymxdaDmTpJc2NXlpn.rJU5J/KwzTaSjqN7hLXRnplhOfo6pY2',
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
                'password' => '$2y$10$ulq/9U6VcHHAl66K1uWVdOkb/oa2X6alk0wmvfZHli6QTVBrkKf9S',
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
                'password' => '$2y$10$w4f52HdpxDLGlLJuLnDAm.AcPJBIAAzmkA8IysLOdyFykQMdVRBUe',
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
                'password' => '$2y$10$EvHMHujIwDtwCoJFAWnlce9hDSseTPJrxEoYFAygWl.BxCVC89Tx6',
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
                'password' => '$2y$10$7saTJwkVI59vY4MFXdifbO893SSWrSNXu7kLT72dj5s9rnEXfK4za',
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
                'password' => '$2y$10$OV4mUtso9c8PeLJ6IDA.eep9Sn6jBS5kkQcAckgirTUEU5yib3Sfq',
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
                'password' => '$2y$10$1ddXOPD45suvMOKHrNzHaeBYH4.jjDB13e7iDYXKL1caW3Mpshatu',
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
                'password' => '$2y$10$/4f8pO8GpEHK251HayqQuebbJf7bmAWUZPaI/SrbLzoOXbg0ETAVW',
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
                'password' => '$2y$10$MezozpfLxV22iaOaLGpYSO0iYg2yg5SBUcuaKSw1/MMzcwS7MHKAG',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['metadata.editor', 'dar.reviewer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Loki',
                'lastname' => 'Sinclair',
                'email' => 'Loki.Sinclair@hdruk.ac.uk',
                'password' => '$2y$10$8VIsXP74R.vSsfs3OqOFveAY91xqAGI4th6p3K1uOdtK.xKTyGERm',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['metadata.editor', 'dar.reviewer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Clara',
                'lastname' => 'Fennessy',
                'email' => 'Clara.Fennessy@hdruk.ac.uk',
                'password' => '$2y$10$rVLGFXe1aBHPTCgc8pkquOyGFU78Z3AGcun6cjsQFpqqvVtfOFxrS',
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
                'password' => '$2y$10$l5NkolzoLeIFGA9kwVcg4.aeRcoxEKp98wFCPvXROHZccL9vKDAcm',
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
                'password' => '$2y$10$UBdYvWJSMxyL27V8LF.A8.HqwF/.7ipzL9/cJ1.YV6/2rdguh6oBK',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['metadata.editor', 'dar.reviewer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Chris',
                'lastname' => 'Milner',
                'email' => 'Chris.Milner@hdruk.ac.uk',
                'password' => '$2y$10$g1hMGuKX7shc77Hnh72gFetDePdSjOMA2N.k2/qoJu8mXQ60X92Ea',
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
                'password' => '$2y$10$Eloa8z9NAUIPUGcR9capaesafqsVYED0fmPV7//AKyxDiRLZHXSFG',
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
                'password' => '$2y$10$30LCSeHP0gS.SowwsE58.uU1I3s6dq0LaRLVXT9hn5ZQrJu3/aI7.',
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
                'password' => '$2y$10$Bw..6rZkJE3mIudA.VWkn.w70YAtWOHpOoGDmHNTEb0hIcNVcMxF2',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['metadata.editor', 'dar.reviewer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Calum',
                'lastname' => 'Macdonald',
                'email' => 'calum.macdonald@hdruk.ac.uk',
                'password' => '$2y$10$wX/KyNneMWXHYQsLvweJ1eIQDS/3LyT5dZvx2Om3uQoQbnEyp3BGK',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Dev Testing',
            ],
            [
                'firstname' => 'Alistair',
                'lastname' => 'Hopper',
                'email' => 'alistair.hopper@nhs.net',
                'password' => '$2y$10$JnvMqRp9xm1guotyu7NonuXzVxkVj/USMq0uMoztAUQHhU9dD0JQS',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],
            [
                'firstname' => 'Al-Fahad',
                'lastname' => 'Abdul-Mumuni',
                'email' => 'al-fahad.abdul-mumuni2@nhs.net',
                'password' => '$2y$10$S8zVYC3mqBGGv12muhOuzeenxdyDRtXtOA33VfEjo/g3smfWDV1Zm',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],
            [
                'firstname' => 'Colleen',
                'lastname' => 'Knight',
                'email' => 'colleenknight@nhs.net',
                'password' => '$2y$10$jKedfR8s6klHZx7jcayrAOX/.BnI87As1UYfi9/ILdn.d6XP5w5y2',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['custodian.team.admin'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],
            [
                'firstname' => 'James',
                'lastname' => 'Richardson',
                'email' => 'james.richardson34@nhs.net',
                'password' => '$2y$10$Uzin1uIEOF6wP9jnHC4Qv.OUK8SzWnNvapgUqP9qO0MJBnc6g95K6',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],
            [
                'firstname' => 'James',
                'lastname' => 'Mcshane',
                'email' => 'jamesmcshane@nhs.net',
                'password' => '$2y$10$PcNqRWOaiYkRcgBHPaj.iu1n9eBQaZmVMVq41.4FrEX7wtmY1k8gu',
                'provider' => 'service',
                'isAdmin' => true,
                'roles' => ['developer'],
                'assignTeam' => true,
                'teamName' => 'Northwest SDE',
            ],

        ];

        foreach ($users as $user) {
            $isUser = User::where(['email' => $user['email']])->first();
            if ($isUser) {
                continue;
            }

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
            // $realPassword = $this->generatePassword(15);
            // $hashPassword = \Hash::make($realPassword);
            // print_r("\npassword :: " . $realPassword . " - hash :: " . $hashPassword . " \n");

            $user = User::factory()->create([
                'name' => $firstname . ' ' . $lastname,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'provider' => $provider,
                'password' => $password,
                'is_admin' => $isAdmin,
                'preferred_email' => 'primary',
                'secondary_email' => null,
                'organisation' => null,
                'bio' => null,
                'domain' => null,
                'link' => null,
                'orcid' => null,
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

    private function generatePassword($length = 15)
    {
        // Define the characters that can be used in the password
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789';
        $password = ''; // Initialize the password string

        // Generate the password
        for ($i = 0; $i < $length; $i++) {
            // Pick a random character from the $chars string and append it to the password
            $randomIndex = rand(0, strlen($chars) - 1);
            $password .= $chars[$randomIndex];
        }

        return $password;
    }
}
