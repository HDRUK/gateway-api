<?php

namespace App\Http\Resources\Api\V1;

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
            'projectGrantName' => $version->project_grant_name,
            'leadResearcher' => $version->lead_researcher,
            'leadResearchInstitute' => $version->lead_research_institute,
            'grantNumbers' => $version->grant_numbers,
            'projectGrantStartDate' => $version->project_grant_start_date,
            'projectGrantEndDate' => $version->project_grant_end_date,
            'projectGrantScope' => $version->project_grant_scope,
            'publications' => $this->whenLoaded('publications'),
            'tools' => $this->whenLoaded('tools'),
            'created_at' => $version->created_at,
            'updated_at' => $version->updated_at,
            'deleted_at' => $version->deleted_at,
        ];
    }
}
