<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CancerTypeFilterResource extends JsonResource
{
    /**
     * The CancerTypeFilter service currently returns arrays (already shaped)
     * to keep recursion simple. This resource intentionally passes through.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return is_array($this->resource) ? $this->resource : parent::toArray($request);
    }
}
