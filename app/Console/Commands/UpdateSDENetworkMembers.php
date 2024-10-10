<?php

namespace App\Console\Commands;

use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Team;
use Exception;
use Illuminate\Console\Command;

class UpdateSDENetworkMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-sde-network-members';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update members of the SDE network';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teamNames = [
            "PIONEER: Supported by the West Midlands Secure Data Environment (SDE)",
            "East of England Secure Data Environment (SDE)",
            "North West Secure Data Environment (SDE)",
            "Thames Valley and Surrey Secure Data Environment (SDE)",
            "South West Secure Data Environment (SDE)",
            "East Midlands Secure Data Environment (SDE)",
            "North East and North Cumbria Secure Data Environment (SDE)",
            "Yorkshire and Humber Secure Data Environment (SDE)",
            "Wessex Secure Data Environment (SDE)",
            "NHS England Secure Data Environment (SDE)",
            "Kent, Medway and Sussex Secure Data Environment (SDE)",
            "Discover-NOW: Part of the London Secure Data Environment (SDE)",
            "INSIGHT UHB: Part of the West Midlands Secure Data Environment (SDE)",
        ];

        try {

            $teamIds = Team::whereIn('name', $teamNames)->pluck('id');

            if (count($teamIds) !== count($teamNames)) {
                $this->warn('Some team names were not found in the database');
            }

            $sdeNetwork = DataProviderColl::where('name', 'like', '%SDE%')->first();
            if ($sdeNetwork) {
                DataProviderCollHasTeam::where(
                    'data_provider_coll_id',
                    $sdeNetwork->id
                )->delete();
                foreach ($teamIds as $t) {
                    DataProviderCollHasTeam::create([
                        'data_provider_coll_id' => $sdeNetwork->id,
                        'team_id' => $t,
                    ]);
                }
            } else {
                $this->warn('SDE custodian network not found');
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
