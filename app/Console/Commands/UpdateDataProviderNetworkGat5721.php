<?php

namespace App\Console\Commands;

use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Team;
use Illuminate\Console\Command;

class UpdateDataProviderNetworkGat5721 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-data-provider-network-gat5721';

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
        // available for testing just in production
        $dataProviderNetworkId = 3;
        $teamId = 103;

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
        } else {
            $this->warn('Data Provider Network ' . $dataProviderNetworkId . ' has already been linked to team ' . $teamId);
        }

    }
}
