<?php

namespace App\Services;

use App\Models\CancerTypeFilter;
use App\Exceptions\BadRequestException;
use Illuminate\Support\Facades\DB;
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
    // Mutations
    // -------------------------------------------------------------------------

    public function create(array $data): array
    {
        $data['level'] = $this->levelForParent($data['parent_id'] ?? null);
        $filter = CancerTypeFilter::create($data);

        return $this->formatFilter($filter);
    }

    /**
     * @throws BadRequestException when the new parent would create a cycle
     */
    public function update(string $filterId, array $data): ?array
    {
        $filter = CancerTypeFilter::where('filter_id', $filterId)->first();
        if (!$filter) {
            return null;
        }

        DB::transaction(function () use ($filter, $data) {
            if (array_key_exists('parent_id', $data)) {
                $this->assertValidParent($filter, $data['parent_id']);
                $data['level'] = $this->levelForParent($data['parent_id']);
            }

            $filter->update($data);

            if ($filter->wasChanged('level')) {
                $this->relevelDescendants($filter);
            }
        });

        return $this->formatFilter($filter->fresh('children'));
    }

    public function delete(string $filterId): bool
    {
        $filter = CancerTypeFilter::where('filter_id', $filterId)->first();
        if (!$filter) {
            return false;
        }

        DB::transaction(function () use ($filter) {
            CancerTypeFilter::whereIn('id', $this->descendantIds($filter))->delete();
            $filter->delete();
        });

        return true;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function levelForParent(?int $parentId): int
    {
        if (is_null($parentId)) {
            return 0;
        }

        return (int) CancerTypeFilter::whereKey($parentId)->value('level') + 1;
    }

    private function assertValidParent(CancerTypeFilter $filter, ?int $parentId): void
    {
        if (is_null($parentId)) {
            return;
        }

        if ($parentId === $filter->id || in_array($parentId, $this->descendantIds($filter), true)) {
            throw new BadRequestException('A filter cannot be moved under itself or one of its descendants');
        }
    }

    private function relevelDescendants(CancerTypeFilter $filter): void
    {
        foreach ($filter->children()->get() as $child) {
            $child->update(['level' => $filter->level + 1]);
            $this->relevelDescendants($child);
        }
    }

    /**
     * @return array<int>
     */
    private function descendantIds(CancerTypeFilter $filter): array
    {
        $ids = [];
        $frontier = [$filter->id];

        while (!empty($frontier)) {
            $frontier = CancerTypeFilter::whereIn('parent_id', $frontier)->pluck('id')->map('intval')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

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
            'description' => $filter->description,
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
