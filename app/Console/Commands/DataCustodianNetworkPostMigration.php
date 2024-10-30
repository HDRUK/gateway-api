<?php

namespace App\Console\Commands;

use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Team;
use Exception;
use Illuminate\Console\Command;

class DataCustodianNetworkPostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:data-custodian-network-post-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        $dataCustodianNetworks = [
            [
                'name' => 'Scottish Safe Haven Network',
                'logo' => '/data-custodian-network/scottish-safe-haven-network.png',
                'description' => 'The Scottish Safe Haven Network (SSHN), which began operations in 2014, is responsible for managing access to NHS Scotland data for secondary use that benefits the public. Coordinated by Research Data Scotland, the network is made up of four accredited regional Safe Havens (DaSH, Data Loch, Glasgow, and HIC) and one national Safe Haven (eDRIS) that cover the entire Scottish population of 5.4 million. The network, which was established to provide researchers with a data access service that uses secure computing environments that comply with the "Five Safes”, operates under the Safe Haven Charter and has gained extensive expertise in data linkage. The regional Safe Havens have access to detailed, individual-level data such as laboratory data, hospital visits, and digital echocardiograms (ECGs), while the national Safe Haven provides access to whole population data such as the Scottish Mortality Records.',
                'teams' => [
                    'Public Health Scotland',
                    'DataLoch',
                    'Health Informatics Centre - UNIVERSITY OF DUNDEE',
                    'West of Scotland Safe Haven',
                    'Grampian Data Safe Haven (DaSH) - University of Aberdeen',
                ],
            ],

            [
                'name' => 'Secure eResearch Platform (SeRP)',
                'logo' => '/data-custodian-network/serp.png',
                'description' => 'SeRP UK is operated as a private research cloud and is operated as a multi-tenancy model which means you control how and who uses it all under one pricing structure. It lets you build economies of scale and offers a cost effective data management and governance environment for users whilst also enabling them to be part of the SeRP UK research community. 
                SeRP UK is accredited to the ISO27001 information security standard. SeRP UK is used by many research organisations across the UK to host research data within a secure research environment enabling collaborative research.
                SeRP UK is the perfect solution for any organisation that has accumulated a large amount of data, that intends to share that data for the purposes of research and long term benefit to society, and that wants to do so in the most secure and safe way possible to reduce associated risks.',
                'teams' => [
                    'DPUK DATA Portal',
                    'BREATHE',
                    'UK Longitudinal Linkage Collaboration (UK LLC)',
                ],
            ],

            [
                'name' => 'SNSDE',
                'logo' => '/data-custodian-network/snsde-network.png',
                'description' => 'The NHS Research Secure Data Environment (SDE) Network is made up of 12 SDEs who provide secure access to healthcare data for research and innovation.',
                'teams' => [
                    'Wessex SDE',
                    'NHS England',
                    'KMS SDE',
                    'Connected Bradford',
                    'DISCOVER NOW',
                    'INSIGHT',
                    'GUT REACTION',
                    'North West SDE',
                    'South West SDE',
                ],
            ],
        ];

        try {
            DataProviderCollHasTeam::truncate();
            DataProviderColl::truncate();

            foreach ($dataCustodianNetworks as $item) {
                $array = [
                    'name' => $item['name'],
                    'img_url' => $item['logo'],
                    'summary' => $item['description'],
                    'enabled' => 1,
                ];
                $teams = $item['teams'];

                $dataProvider = DataProviderColl::create($array);
                $dataProviderId = $dataProvider->id;
                $this->info('Data Custodian Network ' . $item['name'] . ' created with id :: ' . $dataProviderId);

                foreach ($teams as $teamName) {
                    $team = Team::where(\DB::raw("REPLACE(name, '&ndash;', '-')"), 'LIKE', "%{$teamName}%")->first();

                    if ($team) {
                        DataProviderCollHasTeam::create([
                            'data_provider_coll_id' => $dataProviderId,
                            'team_id' => $team->id,
                        ]);
                        $this->info('Team ' . $teamName . ' was related with ' . $item['name']);
                    } else {
                        $this->warn('Team ' . $teamName . ' not found');
                    }
                }
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
