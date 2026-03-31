<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectGrantVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_grant_id' => $this->project_grant_id,
            'version' => $this->version,
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
