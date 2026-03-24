<?php

namespace App\Services;

use App\Models\CancerTypeFilter;

class CancerTypeFilterService
{
    public function list(?int $parentId, ?int $level): array
    {
        if (!is_null($parentId)) {
            $query = CancerTypeFilter::where('parent_id', $parentId);
        } else {
            $query = CancerTypeFilter::whereNull('parent_id');
        }

        if (!is_null($level)) {
            $query->where('level', $level);
        }

        $filters = $query->orderBy('sort_order')->get();
        $filters->load('children');

        return $this->buildHierarchy($filters);
    }

    public function findByFilterId(string $filterId): ?array
    {
        $filter = CancerTypeFilter::with('children')->where('filter_id', $filterId)->first();
        if (!$filter) {
            return null;
        }

        return $this->formatFilter($filter);
    }

    /**
     * Build hierarchical structure from flat collection.
     */
    private function buildHierarchy($filters): array
    {
        $result = [];
        foreach ($filters as $filter) {
            $result[] = $this->formatFilter($filter);
        }

        return $result;
    }

    /**
     * Format filter with children recursively.
     */
    private function formatFilter($filter): array
    {
        $formatted = [
            'id' => $filter->id,
            'filter_id' => $filter->filter_id,
            'label' => $filter->label,
            'category' => $filter->category,
            'primary_group' => $filter->primary_group,
            'count' => $filter->count,
            'parent_id' => $filter->parent_id,
            'level' => $filter->level,
            'sort_order' => $filter->sort_order,
        ];

        if (!$filter->relationLoaded('children')) {
            $filter->load('children');
        }

        if ($filter->children->isNotEmpty()) {
            $formatted['children'] = [];
            foreach ($filter->children->sortBy('sort_order') as $child) {
                $formatted['children'][] = $this->formatFilter($child);
            }
        } else {
            $formatted['children'] = [];
        }

        return $formatted;
    }
}

