<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Role;
use App\Models\TeamHasUser;
use App\Models\CancerTypeFilter;
use App\Models\TeamUserHasRole;
use Tests\Traits\MockExternalApis;

class CancerTypeFilterCrudTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/cancer-type-filters';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function makeFilter(string $filterId, ?int $parentId = null, int $level = 0): CancerTypeFilter
    {
        return CancerTypeFilter::create([
            'filter_id' => $filterId,
            'label' => 'Label ' . $filterId,
            'description' => null,
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '0',
            'parent_id' => $parentId,
            'level' => $level,
            'sort_order' => 0,
        ]);
    }

    public function test_store_creates_filter_and_derives_level_from_parent(): void
    {
        $root = $this->makeFilter('70_0');

        $response = $this->json('POST', self::TEST_URL, [
            'filter_id' => '70_0_1',
            'label' => 'New Child',
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'parent_id' => $root->id,
        ], $this->header);

        $response->assertStatus(201);
        $response->assertJsonPath('data.filter_id', '70_0_1');
        $response->assertJsonPath('data.level', 1);
        $response->assertJsonPath('data.parent_id', $root->id);

        $this->assertDatabaseHas('cancer_type_filters', ['filter_id' => '70_0_1', 'level' => 1]);
    }

    public function test_store_rejects_duplicate_and_malformed_filter_id(): void
    {
        $this->makeFilter('71_0');

        $this->json('POST', self::TEST_URL, ['filter_id' => '71_0', 'label' => 'Dup'], $this->header)
            ->assertStatus(400);

        $this->json('POST', self::TEST_URL, ['filter_id' => 'abc', 'label' => 'Bad'], $this->header)
            ->assertStatus(400);
    }

    public function test_store_requires_authentication(): void
    {
        $this->json('POST', self::TEST_URL, ['filter_id' => '72_0', 'label' => 'Anon'], ['Accept' => 'application/json'])
            ->assertStatus(401);

        $this->assertDatabaseMissing('cancer_type_filters', ['filter_id' => '72_0']);
    }

    public function test_update_replaces_fields(): void
    {
        $this->makeFilter('73_0');

        $response = $this->json('PUT', self::TEST_URL . '/73_0', [
            'filter_id' => '73_0',
            'label' => 'Replaced',
            'description' => 'Replaced description',
            'category' => 'filters',
            'primary_group' => 'cancer-type',
            'count' => '5',
            'parent_id' => null,
            'sort_order' => 2,
        ], $this->header);

        $response->assertStatus(200);
        $response->assertJsonPath('data.label', 'Replaced');
        $response->assertJsonPath('data.sort_order', 2);
    }

    public function test_edit_moves_subtree_and_relevels_descendants(): void
    {
        $rootA = $this->makeFilter('74_0');
        $rootB = $this->makeFilter('75_0');
        $child = $this->makeFilter('74_0_1', $rootA->id, 1);
        $grandchild = $this->makeFilter('74_0_1_1', $child->id, 2);

        $nested = $this->makeFilter('75_0_1', $rootB->id, 1);

        $response = $this->json('PATCH', self::TEST_URL . '/74_0', ['parent_id' => $nested->id], $this->header);

        $response->assertStatus(200);
        $response->assertJsonPath('data.level', 2);
        $this->assertSame(3, $child->fresh()->level);
        $this->assertSame(4, $grandchild->fresh()->level);
    }

    public function test_edit_rejects_moving_filter_under_its_descendant(): void
    {
        $root = $this->makeFilter('76_0');
        $child = $this->makeFilter('76_0_1', $root->id, 1);

        $this->json('PATCH', self::TEST_URL . '/76_0', ['parent_id' => $child->id], $this->header)
            ->assertStatus(400);

        $this->json('PATCH', self::TEST_URL . '/76_0', ['parent_id' => $root->id], $this->header)
            ->assertStatus(400);
    }

    public function test_edit_returns_404_for_unknown_filter_id(): void
    {
        $this->json('PATCH', self::TEST_URL . '/0_0_999', ['label' => 'Nope'], $this->header)
            ->assertStatus(404);
    }

    public function test_destroy_deletes_filter_and_descendants(): void
    {
        $root = $this->makeFilter('77_0');
        $child = $this->makeFilter('77_0_1', $root->id, 1);
        $this->makeFilter('77_0_1_1', $child->id, 2);
        $this->makeFilter('78_0');

        $this->json('DELETE', self::TEST_URL . '/77_0', [], $this->header)
            ->assertStatus(200);

        $this->assertDatabaseMissing('cancer_type_filters', ['filter_id' => '77_0']);
        $this->assertDatabaseMissing('cancer_type_filters', ['filter_id' => '77_0_1']);
        $this->assertDatabaseMissing('cancer_type_filters', ['filter_id' => '77_0_1_1']);
        $this->assertDatabaseHas('cancer_type_filters', ['filter_id' => '78_0']);
    }

    public function test_destroy_returns_404_for_unknown_filter_id(): void
    {
        $this->json('DELETE', self::TEST_URL . '/0_0_999', [], $this->header)
            ->assertStatus(404);
    }

    public function test_store_forbidden_without_cancer_type_filter_permission(): void
    {
        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['filter_id' => '79_0', 'label' => 'No Permission'],
            $headerNonAdmin
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        $this->assertDatabaseMissing('cancer_type_filters', ['filter_id' => '79_0']);
    }

    public function test_store_allows_team_member_with_cancer_type_filter_permission(): void
    {
        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $nonAdminUser = $this->getUserFromJwt($nonAdminJwt);
        $headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];

        $teamId = TeamHasUser::query()->first()->team_id;
        $teamHasUser = TeamHasUser::firstOrCreate([
            'user_id' => $nonAdminUser['id'],
            'team_id' => $teamId,
        ]);

        // custodian.metadata.manager holds the cancer_type_filters.* permissions
        $role = Role::where('name', 'custodian.metadata.manager')->first();
        TeamUserHasRole::firstOrCreate([
            'team_has_user_id' => $teamHasUser->id,
            'role_id' => $role->id,
        ]);

        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['filter_id' => '80_0', 'label' => 'Permitted Filter'],
            $headerNonAdmin
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('cancer_type_filters', [
            'filter_id' => '80_0',
            'label' => 'Permitted Filter',
        ]);
    }

    public function test_update_forbidden_without_cancer_type_filter_permission(): void
    {
        $this->makeFilter('81_0');

        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];

        $response = $this->json(
            'PUT',
            self::TEST_URL . '/81_0',
            [
                'filter_id' => '81_0',
                'label' => 'Forbidden Update',
                'count' => '0',
                'parent_id' => null,
                'sort_order' => 0,
            ],
            $headerNonAdmin
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        $this->assertDatabaseMissing('cancer_type_filters', [
            'filter_id' => '81_0',
            'label' => 'Forbidden Update',
        ]);
    }
}
