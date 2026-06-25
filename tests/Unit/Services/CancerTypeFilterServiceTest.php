<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\CancerTypeFilter;
use App\Services\CancerTypeFilterService;

class CancerTypeFilterServiceTest extends TestCase
{
    private CancerTypeFilterService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new CancerTypeFilterService();
    }

    public function test_list_builds_hierarchy_for_root_filters(): void
    {
        $root = CancerTypeFilter::create([
            'filter_id' => 'svc_0_0',
            'label' => 'Root',
            'description' => 'Root description',
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => null,
            'level' => 0,
            'sort_order' => 0,
        ]);

        CancerTypeFilter::create([
            'filter_id' => 'svc_0_0_1',
            'label' => 'Child',
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => $root->id,
            'level' => 1,
            'sort_order' => 0,
        ]);

        $result = $this->service->list(parentId: null, level: null);

        $match = collect($result)->firstWhere('filter_id', 'svc_0_0');
        $this->assertNotNull($match);
        $this->assertSame('Root description', $match['description']);
        $this->assertCount(1, $match['children']);
        $this->assertSame('svc_0_0_1', $match['children'][0]['filter_id']);
    }

    public function test_list_filters_by_parent_id(): void
    {
        $root = CancerTypeFilter::create([
            'filter_id' => 'svc_1_0',
            'label' => 'Root',
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => null,
            'level' => 0,
            'sort_order' => 0,
        ]);

        CancerTypeFilter::create([
            'filter_id' => 'svc_1_0_1',
            'label' => 'Child',
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => $root->id,
            'level' => 1,
            'sort_order' => 0,
        ]);

        $result = $this->service->list(parentId: $root->id, level: null);

        $this->assertCount(1, $result);
        $this->assertSame('svc_1_0_1', $result[0]['filter_id']);
        $this->assertSame([], $result[0]['children']);
    }

    public function test_find_by_filter_id_returns_formatted_filter(): void
    {
        $filter = CancerTypeFilter::create([
            'filter_id' => 'svc_2_0',
            'label' => 'Lookup',
            'description' => 'Lookup description',
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => null,
            'level' => 0,
            'sort_order' => 0,
        ]);

        $result = $this->service->findByFilterId('svc_2_0');

        $this->assertIsArray($result);
        $this->assertSame($filter->id, $result['id']);
        $this->assertSame('Lookup', $result['label']);
        $this->assertSame('Lookup description', $result['description']);
    }

    public function test_find_by_filter_id_returns_null_when_missing(): void
    {
        $this->assertNull($this->service->findByFilterId('svc_missing'));
    }
}
