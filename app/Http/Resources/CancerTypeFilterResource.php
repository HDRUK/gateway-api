<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CancerTypeFilterResource extends JsonResource
{
    public function toArray($request): array
    {
        $children = $this['children'] ?? [];

        return [
            'id' => $this['id'] ?? null,
            'filter_id' => $this['filter_id'] ?? null,
            'label' => $this['label'] ?? null,
            'category' => $this['category'] ?? null,
            'primary_group' => $this['primary_group'] ?? null,
            'count' => $this['count'] ?? null,
            'parent_id' => $this['parent_id'] ?? null,
            'level' => $this['level'] ?? null,
            'sort_order' => $this['sort_order'] ?? null,
            'children' => array_map(
                fn ($child) => self::make($child)->resolve($request),
                is_array($children) ? $children : []
            ),
        ];
    }
}

