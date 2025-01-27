<?php

namespace App\Console\Commands;

use App\Models\DataProviderCollHasTeam;
use Illuminate\Console\Command;

class AddNewNencTeamToSdeGat6268 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-new-nenc-team-to-sde-gat6268';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GAT-6268 :: Add new NENC team to SDE Network page and remove old one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Add Data Custodian ID 110 to Data Custodian Network Page: Data Custodian Network - Health Data Research Gateway (ID: 3)
        $dataProviderHasTeam = DataProviderCollHasTeam::where([
            'data_provider_coll_id' => 3,
            'team_id' => 110,
        ])->first();

        if (is_null($dataProviderHasTeam)) {
            DataProviderCollHasTeam::create([
                'data_provider_coll_id' => 3,
                'team_id' => 110,
            ]);
        }

        // 2. Remove data custodian ID 99 from Data Custodian network page (ID: 3) .
        $dataProviderHasTeam = DataProviderCollHasTeam::where([
            'data_provider_coll_id' => 3,
            'team_id' => 99,
        ])->first();

        if (!is_null($dataProviderHasTeam)) {
            DataProviderCollHasTeam::where([
                'data_provider_coll_id' => 3,
                'team_id' => 99,
            ])->delete();
        }
    }
}
