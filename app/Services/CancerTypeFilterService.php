<?php

namespace App\Services;

use App\Models\CancerTypeFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CancerTypeFilterService
{
    // -------------------------------------------------------------------------
    // Querying
    // -------------------------------------------------------------------------

    public function list(?int $parentId, ?int $level): array
    {
        $query = $this->baseQuery($parentId, $level);
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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function baseQuery(?int $parentId, ?int $level): Builder
    {
        $query = CancerTypeFilter::query();

        if (!is_null($parentId)) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        if (!is_null($level)) {
            $query->where('level', $level);
        }

        return $query;
    }

    /**
     * Build hierarchical structure from flat collection.
     */
    private function buildHierarchy(Collection $filters): array
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
    private function formatFilter(CancerTypeFilter $filter): array
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

