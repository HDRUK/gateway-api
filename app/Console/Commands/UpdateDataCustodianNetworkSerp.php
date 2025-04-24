<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;

class UpdateDataCustodianNetworkSerp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-data-custodian-network-serp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update data custodian network serp';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // add team_id 19 and 115 to data_provider_coll_id 2
        // remove team_id 104 from data_provider_coll_id 2
        $add = [19, 115];
        $remove = [104];
        $dataProviderNetworkId = 2;

        foreach ($add as $teamId) {
            $this->addTeamToDataProviderNetwork($dataProviderNetworkId, $teamId);
        }

        foreach ($remove as $teamId) {
            $this->removeTeamToDataProviderNetwork($dataProviderNetworkId, $teamId);
        }

        $this->info('Completed ...');
    }

    public function addTeamToDataProviderNetwork($dataProviderNetworkId, $teamId)
    {
        $checkDataProviderNetworkId = DataProviderColl::where('id', $dataProviderNetworkId)->first();
        if (is_null($checkDataProviderNetworkId)) {
            $this->warn('Data Provider Network not found for id ' . $dataProviderNetworkId);
            return;
        }

        $checkTeam = Team::where('id', $teamId)->first();
        if (is_null($checkTeam)) {
            $this->warn('Team not found for id ' . $teamId);
            return;
        }

        $checkDataProviderCollHasTeam = DataProviderCollHasTeam::where([
            'data_provider_coll_id' => $dataProviderNetworkId,
            'team_id' => $teamId,
        ])->first();

        if (is_null($checkDataProviderCollHasTeam)) {
            DataProviderCollHasTeam::create([
                'data_provider_coll_id' => $dataProviderNetworkId,
                'team_id' => $teamId,
            ]);
            $this->info('Data Provider Network ' . $dataProviderNetworkId . ' has been linked to team ' . $teamId);
        }
    }

    public function removeTeamToDataProviderNetwork($dataProviderNetworkId, $teamId)
    {
        $checkDataProviderNetworkId = DataProviderColl::where('id', $dataProviderNetworkId)->first();
        if (is_null($checkDataProviderNetworkId)) {
            $this->warn('Data Provider Network not found for id ' . $dataProviderNetworkId);
            return;
        }

        $checkTeam = Team::where('id', $teamId)->first();
        if (is_null($checkTeam)) {
            $this->warn('Team not found for id ' . $teamId);
            return;
        }

        $checkDataProviderCollHasTeam = DataProviderCollHasTeam::where([
            'data_provider_coll_id' => $dataProviderNetworkId,
            'team_id' => $teamId,
        ])->first();

        if (!is_null($checkDataProviderCollHasTeam)) {
            DataProviderCollHasTeam::where([
                'data_provider_coll_id' => $dataProviderNetworkId,
                'team_id' => $teamId,
            ])->delete();
            $this->info('Data Provider Network ' . $dataProviderNetworkId . ' has been unlinked to team ' . $teamId);
        }
    }

}
