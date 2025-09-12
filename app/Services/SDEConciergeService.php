<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Dataset;
use App\Models\DataProviderColl;

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
        if (count($teamIds) === 1) {
            // Enquiry goes to team that uploaded metadata
            return;
        }

        if (empty(array_diff($teamIds, $sdeInTeams))) {
            // Enquiry goes to SDE Concierge, as all teams are SDE
            $teamIds = [$conciergeId];
            $teamNames = [$conciergeName];
            return;
        }

        if (!empty($sdeInTeams) && !empty(array_diff($teamIds, $sdeInTeams))) {
            // Enquiry goes to SDE Concierge _and_ teams that uploaded metadata
            $nonSdeTeamIds = array_diff($teamIds, $sdeInTeams);
            $nonSdeTeamNames = array_map(fn ($id) => Team::find($id)->name, $nonSdeTeamIds);

            $teamIds = array_merge([$conciergeId], $nonSdeTeamIds);
            $teamNames = array_merge([$conciergeName], $nonSdeTeamNames);
            return;
        }
    }

    public function determineEnquiryToSdeTeamOrConcierge(array &$teamIds, array &$teamNames, array $sdeInTeams, int $conciergeId, string $conciergeName): void
    {
        if (count($sdeInTeams) > 1 || (count($sdeInTeams) === 1 && count($teamIds) > 1)) {
            // Enquiry goes to SDE Concierge, as multiple SDE teams or SDE and non-SDE teams
            $teamIds = array_values(array_diff($teamIds, $sdeInTeams));
            $teamIds[] = $conciergeId;

            $teamNames = array_values(array_diff($teamNames, array_map(fn ($id) => Team::find($id)->name, $sdeInTeams)));
            $teamNames[] = $conciergeName;
            return;
        }

        // Enquiry goes to team that uploaded metadata
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

    public function shouldUseConcierge(int $teamId, array $sdeTeamIds): bool
    {
        return in_array($teamId, $sdeTeamIds);
    }

    public function getTeamFromDataset($dataset): Team
    {
        $gatewayId = $dataset->latestMetadata->metadata['metadata']['summary']['publisher']['gatewayId'] ?? null;
        return is_numeric($gatewayId)
            ? Team::find((int) $gatewayId)
            : Team::where('pid', $gatewayId)->first();
    }
}
