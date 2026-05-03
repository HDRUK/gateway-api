<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ProjectGrantVersion;

class ProjectGrantVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ProjectGrantVersion $version */
        $version = $this->resource;

        return [
            'id' => $version->id,
            'project_grant_id' => $version->project_grant_id,
            'version' => $version->version,
            'projectGrantName' => $version->projectGrantName,
            'leadResearcher' => $version->leadResearcher,
            'leadResearchInstitute' => $version->leadResearchInstitute,
            'grantNumbers' => $version->grantNumbers,
            'projectGrantStartDate' => $version->projectGrantStartDate,
            'projectGrantEndDate' => $version->projectGrantEndDate,
            'projectGrantScope' => $version->projectGrantScope,
            'publications' => $this->whenLoaded('publications'),
            'tools' => $this->whenLoaded('tools'),
            'created_at' => $version->created_at,
            'updated_at' => $version->updated_at,
            'deleted_at' => $version->deleted_at,
        ];
    }
}
