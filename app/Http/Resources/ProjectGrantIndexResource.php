<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectGrantIndexResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

