<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectGrantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
            'version' => $this->version,
            'pid' => $this->pid,
            'projectGrantName' => $this->projectGrantName,
            'leadResearcher' => $this->leadResearcher,
            'leadResearchInstitute' => $this->leadResearchInstitute,
            'grantNumbers' => $this->grantNumbers,
            'projectGrantStartDate' => $this->projectGrantStartDate,
            'projectGrantEndDate' => $this->projectGrantEndDate,
            'projectGrantScope' => $this->projectGrantScope,
            'datasetVersions' => $this->whenLoaded('datasetVersions'),
            'publications' => $this->whenLoaded('publications'),
            'tools' => $this->whenLoaded('tools'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

