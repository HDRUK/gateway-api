<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Dataset;
use App\Models\DataProviderColl;
use Illuminate\Support\Facades\Log;

class SDEConciergeService
{
    public function getSdeTeamConfiguration(array $datasets, array $input): array
    {
        [$conciergeId, $conciergeName] = $this->getSdeNetworkConcierge();
        $sdeTeamIds = $this->getSdeTeamIds();
        $teamIds = [];
        $teamNames = [];
        $dataCustodians = [];

        if ($input['is_general_enquiry']) {
            $teamIds = array_map(fn ($dataset) => $dataset['team_id'], $datasets);
            $teamNames = array_map(fn ($id) => Team::find($id)->name, $teamIds);

            $sdeInTeams = array_intersect($teamIds, $sdeTeamIds);

            $this->determineGeneralEnquiryToSdeTeamOrConcierge(
                $teamIds,
                $teamNames,
                $sdeInTeams,
                $conciergeId,
                $conciergeName
            );
        } elseif ($input['is_feasibility_enquiry'] || $input['is_dar_dialogue']) {
            $datasetIds = collect($datasets)->pluck('dataset_id');
            $datasetsWithMetadata = Dataset::with('latestMetadata')
                ->whereIn('id', $datasetIds)
                ->get()
                ->keyBy('id');

            foreach ($datasets as $dataset) {
                $datasetModel = $datasetsWithMetadata[$dataset['dataset_id']];
                $team = $this->getTeamFromDataset($datasetModel);
                $teamIds[] = $team->id;
                $teamNames[] = $team->name;
            }

            $sdeInTeams = array_intersect($teamIds, $sdeTeamIds);

            $this->determineEnquiryToSdeTeamOrConcierge(
                $teamIds,
                $teamNames,
                $sdeInTeams,
                $conciergeId,
                $conciergeName
            );
        }

        return [
            'team_ids' => array_unique($teamIds),
            'team_names' => array_unique($teamNames),
            'data_custodians' => array_unique($dataCustodians),
        ];
    }

    public function determineGeneralEnquiryToSdeTeamOrConcierge(
        array &$teamIds,
        array &$teamNames,
        array $sdeInTeams,
        int $conciergeId,
        string $conciergeName
    ): void {
        Log::info('Debugging SDE determineGeneralEnquiryToSdeTeamOrConcierge', [
            'teamIds' => $teamIds,
            'sdeInTeams' => $sdeInTeams,
            'conciergeId' => $conciergeId,
        ]);
        if (count($teamIds) === 1) {
            Log::info('1 <<<<determineGeneralEnquiryToSdeTeamOrConcierge');
            // Enquiry goes to team that uploaded metadata
            return;
        }

        if (empty(array_diff($teamIds, $sdeInTeams))) {
            Log::info('2 <<<<determineGeneralEnquiryToSdeTeamOrConcierge');
            // Enquiry goes to SDE Concierge, as all teams are SDE
            $teamIds = [$conciergeId];
            $teamNames = [$conciergeName];
            return;
        }

        if (!empty($sdeInTeams) && !empty(array_diff($teamIds, $sdeInTeams))) {
            Log::info('3 <<<<determineGeneralEnquiryToSdeTeamOrConcierge');
            // Enquiry goes to SDE Concierge _and_ teams that uploaded metadata
            $nonSdeTeamIds = array_diff($teamIds, $sdeInTeams);
            $nonSdeTeamNames = array_map(fn ($id) => Team::find($id)->name, $nonSdeTeamIds);

            $teamIds = array_merge([$conciergeId], $nonSdeTeamIds);
            $teamNames = array_merge([$conciergeName], $nonSdeTeamNames);
            return;
        }
    }

    public function determineEnquiryToSdeTeamOrConcierge(
        array &$teamIds,
        array &$teamNames,
        array $sdeInTeams,
        int $conciergeId,
        string $conciergeName
    ): void {
        Log::info('Debugging SDE determineEnquiryToSdeTeamOrConcierge', [
            'teamIds' => $teamIds,
            'sdeInTeams' => $sdeInTeams,
            'conciergeId' => $conciergeId,
        ]);

        // CASE 1: Single dataset enquiry
        if (count($teamIds) === 1) {
            Log::info('1 <<<<determineEnquiryToSdeTeamOrConcierge');
            // Whether SDE or non-SDE, honour the custodian
            return;
        }

        // CASE 2: Multi-dataset enquiry, all datasets belong to one SDE team
        if (count($sdeInTeams) === 1 && empty(array_diff($teamIds, $sdeInTeams))) {
            Log::info('2 <<<<determineEnquiryToSdeTeamOrConcierge');
            // All datasets are from a single SDE custodian
            return;
        }

        // CASE 3: Multi-dataset enquiry, multiple SDE custodians
        if (count($sdeInTeams) > 1 && empty(array_diff($teamIds, $sdeInTeams))) {
            Log::info('3 <<<<determineEnquiryToSdeTeamOrConcierge');
            // All datasets are SDE but from different custodians → concierge
            $teamIds = [$conciergeId];
            $teamNames = [$conciergeName];
            return;
        }

        // CASE 4: Multi-dataset enquiry, SDE + non-SDE mix
        if (!empty($sdeInTeams) && !empty(array_diff($teamIds, $sdeInTeams))) {
            Log::info('4 <<<<determineEnquiryToSdeTeamOrConcierge');
            $nonSdeTeamIds = array_diff($teamIds, $sdeInTeams);
            $nonSdeTeamNames = array_map(fn ($id) => Team::find($id)->name, $nonSdeTeamIds);

            $teamIds = array_merge($nonSdeTeamIds, [$conciergeId]);
            $teamNames = array_merge($nonSdeTeamNames, [$conciergeName]);
            return;
        }

        // CASE 5: Multi-dataset enquiry, all non-SDE
        // → honor all custodians
        Log::info('5 <<<<determineEnquiryToSdeTeamOrConcierge');
        return;
    }


    public function getSdeNetworkConcierge(): array
    {
        $team = Team::where('name', 'LIKE', '%SDE Network%')->first(); // why not a boolean flag?!
        if ($team) {
            return [$team->id, $team->name];
        }

        return [null, null];
    }

    public function getSdeTeamIds(): array
    {
        $sdeNetwork = DataProviderColl::where('name', 'LIKE', '%SDE%')
            ->with('teams')
            ->first();

        return $sdeNetwork ? $sdeNetwork->teams->pluck('id')->toArray() : [];
    }

    public function getTeamFromDataset($dataset): Team
    {
        $gatewayId = $dataset->latestMetadata->metadata['metadata']['summary']['publisher']['gatewayId'] ?? null;
        return is_numeric($gatewayId)
            ? Team::find((int) $gatewayId)
            : Team::where('pid', $gatewayId)->first();
    }
}
