<?php

namespace App\Http\Traits;

use App\Models\Dur;
use App\Models\Collection;
use App\Exceptions\NotFoundException;

trait DurV2Helpers
{
    public function getDurById(int $durId, ?int $teamId = null, ?string $status = null)
    {
        $dur = Dur::where(['id' => $durId])
        ->with([
            'keywords',
            'publications',
            'tools',
            // SC: I can't get these fields to work properly when applying a status=ACTIVE condition to the underlying entity. 
            // I don't think the FE ever uses this information, so I'm disabling it until it ever is required again.
            // 'userDatasets' => function ($query) {
            //     $query->distinct('id');
            // },
            // 'userPublications' => function ($query) {
            //     $query->distinct('id');
            // },
            // 'applicationDatasets' => function ($query) {
            //     $query->distinct('id');
            // },
            // 'applicationPublications' => function ($query) {
            //     $query->distinct('id');
            // },
            'user',
            'team',
            'collections' => function ($query) {
                $query->where('status', Collection::STATUS_ACTIVE);
            },
        ])
        ->when($teamId, function ($query) use ($teamId) {
            return $query->where(['team_id' => $teamId]);
        })
        ->when($status, function ($query) use ($status) {
            return $query->where(['status' => $status]);
        })
        ->first();

        if (!$dur) {
            throw new NotFoundException();
        }

        // SC: disabling for now (see comment above)
        // $userDatasets = $dur->userDatasets;
        // $userPublications = $dur->userPublications;
        // $users = $userDatasets->merge($userPublications)
        //     ->unique('id');
        // $dur->setRelation('users', $users);

        // $applicationDatasets = $dur->applicationDatasets;
        // $applicationPublications = $dur->applicationPublications;
        // $applications = $applicationDatasets->merge($applicationPublications)
        //     ->unique('id');
        // $dur->setRelation('applications', $applications);

        // unset(
        //     $users,
        //     $userDatasets,
        //     $userPublications,
        //     $applications,
        //     $applicationDatasets,
        //     $applicationPublications,
        //     $dur->userDatasets,
        //     $dur->userPublications,
        //     $dur->applicationDatasets,
        //     $dur->applicationPublications
        // );

        // Fetch datasets using the accessor
        $datasets = $dur->allDatasets  ?? [];

        foreach ($datasets as &$dataset) {
            $dataset['shortTitle'] = $this->getDatasetTitle($dataset['id']);
        }

        // Update the relationship with the modified datasets
        $dur->setAttribute('datasets', $datasets);

        return $dur->toArray();
    }
}
