<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\CancerTypeFilter;

class CancerTypeFilterTest extends TestCase
{
    public const TEST_URL_INDEX = '/api/v1/cancer-type-filters';

    public function test_index_returns_root_filters_with_children(): void
    {
        $root = CancerTypeFilter::create([
            'filter_id' => '0_0',
            'label' => 'cancerTypes',
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => null,
            'level' => 0,
            'sort_order' => 0,
        ]);

        CancerTypeFilter::create([
            'filter_id' => '0_0_1',
            'label' => 'Child',
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => $root->id,
            'level' => 1,
            'sort_order' => 0,
        ]);

        $response = $this->json('GET', self::TEST_URL_INDEX, [], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'filter_id',
                    'label',
                    'description',
                    'category',
                    'primary_group',
                    'count',
                    'parent_id',
                    'level',
                    'sort_order',
                    'children',
                ],
            ],
        ]);

        $this->assertSame('0_0', $response['data'][0]['filter_id']);
        $this->assertCount(1, $response['data'][0]['children']);
        $this->assertSame('0_0_1', $response['data'][0]['children'][0]['filter_id']);
    }

    public function test_show_returns_404_for_unknown_filter_id(): void
    {
        $response = $this->json('GET', '/api/v1/cancer-type-filters/0_0_999', [], ['Accept' => 'application/json']);
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }

    public function test_index_can_filter_by_parent_id(): void
    {
        $rootA = CancerTypeFilter::create([
            'filter_id' => '0_0',
            'label' => 'RootA',
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => null,
            'level' => 0,
            'sort_order' => 0,
        ]);

        $rootB = CancerTypeFilter::create([
            'filter_id' => '1_0',
            'label' => 'RootB',
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => null,
            'level' => 0,
            'sort_order' => 1,
        ]);

        $childOfB = CancerTypeFilter::create([
            'filter_id' => '1_0_1',
            'label' => 'ChildB',
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => $rootB->id,
            'level' => 1,
            'sort_order' => 0,
        ]);

        $response = $this->json(
            'GET',
            self::TEST_URL_INDEX . '?parent_id=' . $rootB->id,
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
        $this->assertSame($childOfB->filter_id, $response['data'][0]['filter_id']);
    }
}
