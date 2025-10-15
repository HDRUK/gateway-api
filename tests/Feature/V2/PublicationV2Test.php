<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use App\Models\PublicationHasDatasetVersion;

class PublicationV2Test extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v2/publications';

    protected $header = [];

    /**
     * Set up the databse
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Publication::flushEventListeners();
        PublicationHasDatasetVersion::flushEventListeners();
    }

    /**
     * Get all publications with success
     *
     * @return void
     */
    public function test_v2_get_all_publications_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'owner_id',
                    'status',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);
    }

    /**
     * Get Publication by Id with success
     *
     * @return void
     */
    public function test_v2_get_publication_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'paper_title',
                'authors',
                'year_of_publication',
                'paper_doi',
                'publication_type',
                'journal_name',
                'abstract',
                'url',
                'mongo_id',
                'owner_id',
                'status',
                'datasets',
                'tools',
                'collections',
            ]
        ]);
        $response->assertStatus(200);
    }


    public function test_v2_can_filter_publications_not_by_status(): void
    {
        Publication::where("id", 1)->get()->first()->update(['status' => 'ACTIVE']);
        $firstPublicationTitle = Publication::where("id", 1)->get()->first()->paper_title;
        $response = $this->json(
            'GET',
            self::TEST_URL . "?paper_title=" . $firstPublicationTitle,
            $this->header,
        );

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
    }


    public function test_v2_get_publications_by_team_and_by_status_active(): void
    {
        $teamId = $this->getTeamWithStatus('active');
        $response = $this->json('GET', '/api/v2/teams/' . $teamId . '/publications/status/active', [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);

        $teamId = $this->getTeamWithStatus('active');
        $response = $this->json('GET', '/api/v2/teams/' . $teamId . '/publications/status/active', [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);
    }

    public function test_v2_get_publications_by_user_and_by_status_active(): void
    {
        $userId = $this->getUserWithStatus('active');
        $response = $this->json('GET', '/api/v2/users/' . $userId . '/publications/status/active?sort=updated_at:desc', [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);

        $userId = $this->getUserWithStatus('active');
        $response = $this->json('GET', '/api/v2/users/' . $userId . '/publications/status/active', [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);
    }

    public function test_v2_get_publications_by_team_and_by_status_draft(): void
    {
        $teamId = $this->getTeamWithStatus('draft');
        $response = $this->json('GET', '/api/v2/teams/' . $teamId . '/publications/status/draft?sort=updated_at:desc', [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);

        $teamId = $this->getTeamWithStatus('draft');
        $response = $this->json('GET', '/api/v2/teams/' . $teamId . '/publications/status/draft', [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);
    }

    public function test_v2_get_publications_by_team_and_by_archived_draft(): void
    {
        $teamId = $this->getTeamWithStatus('archived');
        $response = $this->json('GET', '/api/v2/teams/' . $teamId . '/publications/status/archived?sort=updated_at:desc', [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);

        $teamId = $this->getTeamWithStatus('archived');
        $response = $this->json('GET', '/api/v2/teams/' . $teamId . '/publications/status/archived', [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);
    }

    public function test_v2_get_publication_by_team_and_by_id(): void
    {
        $publication = Publication::inRandomOrder()->first();

        $response = $this->json('GET', '/api/v2/teams/' . $publication->team_id . '/publications/' . $publication->id, [], $this->header);
        $response->assertJsonStructure([
            'message',
            'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
            ],
        ]);
        $response->assertStatus(200);
    }

    public function test_v2_get_publication_by_user_and_by_id(): void
    {
        $publication = Publication::inRandomOrder()->first();

        $response = $this->json('GET', '/api/v2/users/' . $publication->owner_id . '/publications/' . $publication->id, [], $this->header);
        $response->assertJsonStructure([
            'message',
            'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'paper_title',
                    'authors',
                    'year_of_publication',
                    'paper_doi',
                    'publication_type',
                    'journal_name',
                    'abstract',
                    'url',
                    'mongo_id',
                    'publication_type_mk1',
                    'owner_id',
                    'status',
                    'team_id',
                    'datasets',
                    'tools',
            ],
        ]);
        $response->assertStatus(200);
    }

    public function test_v2_create_publication_with_success_by_team_id(): void
    {
        $teamId = Team::all()->random()->id;
        $datasetId = 1;

        $response = $this->json(
            'POST',
            '/api/v2/teams/' . $teamId . '/publications/',
            [
                'paper_title' => 'Test Paper Title',
                'authors' => 'Einstein, Albert, Yankovich, Al',
                'year_of_publication' => '2013',
                'paper_doi' => '10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
                'url' => 'http://smith.com/cumque-sint-molestiae-minima-corporis-quaerat.html',
                'datasets' => [
                    0 => [
                        'id' => $datasetId,
                        'link_type' => 'USING',
                    ],
                ],
                'tools' => $this->generateTools(),
                'status' => 'ACTIVE',
            ],
            $this->header,
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data'
        ]);

        $pubId = $response->decodeResponseJson()['data'];
        $relation = PublicationHasDatasetVersion::where('publication_id', $pubId)->first();
        $this->assertNotNull($relation);
        $this->assertEquals($relation['link_type'], "USING");

    }

    public function test_v2_create_publication_with_success_by_user_id(): void
    {
        $userId = User::all()->random()->id;
        $datasetId = 1;

        $response = $this->json(
            'POST',
            '/api/v2/users/' . $userId . '/publications/',
            [
                'paper_title' => 'Test Paper Title',
                'authors' => 'Einstein, Albert, Yankovich, Al',
                'year_of_publication' => '2013',
                'paper_doi' => '10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
                'url' => 'http://smith.com/cumque-sint-molestiae-minima-corporis-quaerat.html',
                'datasets' => [
                    0 => [
                        'id' => $datasetId,
                        'link_type' => 'USING',
                    ],
                ],
                'tools' => $this->generateTools(),
                'status' => 'ACTIVE',
            ],
            $this->header,
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data'
        ]);

        $pubId = $response->decodeResponseJson()['data'];
        $relation = PublicationHasDatasetVersion::where('publication_id', $pubId)->first();
        $this->assertNotNull($relation);
        $this->assertEquals($relation['link_type'], "USING");

    }

    public function test_v2_update_active_publication_by_team_id_with_success(): void
    {
        $teamId = Team::all()->random()->id;
        $countBefore = Publication::all()->count();
        $response = $this->json(
            'POST',
            '/api/v2/teams/' . $teamId . '/publications/',
            [
                'paper_title' => 'Test Paper Title',
                'authors' => 'Einstein, Albert, Yankovich, Al',
                'year_of_publication' => '2013',
                'paper_doi' => '10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
                'url' => 'http://smith.com/cumque-sint-molestiae-minima-corporis-quaerat.html',
                'datasets' => [
                    0 => [
                        'id' => 1,
                        'link_type' => 'ABOUT',
                    ],
                ],
                'tools' => $this->generateTools(),
                'status' => 'ACTIVE'
            ],
            $this->header,
        );

        $response->assertStatus(201);

        $countAfter = Publication::all()->count();
        $this->assertTrue((bool) ($countAfter - $countBefore));

        $response->assertJsonStructure([
            'message',
            'data'
        ]);

        $publicationId = (int)$response['data'];

        $responseUpdate = $this->json(
            'PUT',
            '/api/v2/teams/' . $teamId . '/publications/' . $publicationId,
            [
                 'paper_title' => 'Not A Test Paper Title',
                 'authors' => 'Einstein, Albert, Yankovich, Al',
                 'year_of_publication' => '2022',
                 'paper_doi' => '10.1000/182',
                 'publication_type' => 'Paper and such',
                 'journal_name' => 'Something Journal-y here',
                 'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
                 'url' => 'http://smith.com/cumque-sint-molestiae-minima-corporis-quaerat.html',
                 'datasets' => [
                     0 => [
                         'id' => 1,
                         'link_type' => 'USING',
                     ],
                 ],
                 'status' => 'ACTIVE'
             ],
            $this->header,
        );

        $responseUpdate->assertStatus(200);
        $responseUpdate->assertJsonStructure([
            'message',
            'data'
        ]);

        $content = $responseUpdate->decodeResponseJson()['data'];
        $this->assertEquals($content['paper_title'], 'Not A Test Paper Title');
    }

    public function test_v2_update_active_publication_by_user_id_with_success(): void
    {
        $userId = User::all()->random()->id;
        $countBefore = Publication::all()->count();
        $response = $this->json(
            'POST',
            '/api/v2/users/' . $userId . '/publications/',
            [
                'paper_title' => 'Test Paper Title',
                'authors' => 'Einstein, Albert, Yankovich, Al',
                'year_of_publication' => '2013',
                'paper_doi' => '10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
                'url' => 'http://smith.com/cumque-sint-molestiae-minima-corporis-quaerat.html',
                'datasets' => [
                    0 => [
                        'id' => 1,
                        'link_type' => 'ABOUT',
                    ],
                ],
                'tools' => $this->generateTools(),
                'status' => 'ACTIVE'
            ],
            $this->header,
        );

        $response->assertStatus(201);

        $countAfter = Publication::all()->count();
        $this->assertTrue((bool) ($countAfter - $countBefore));

        $response->assertJsonStructure([
            'message',
            'data'
        ]);

        $publicationId = (int)$response['data'];

        $responseUpdate = $this->json(
            'PUT',
            '/api/v2/users/' . $userId . '/publications/' . $publicationId,
            [
                 'paper_title' => 'Not A Test Paper Title',
                 'authors' => 'Einstein, Albert, Yankovich, Al',
                 'year_of_publication' => '2022',
                 'paper_doi' => '10.1000/182',
                 'publication_type' => 'Paper and such',
                 'journal_name' => 'Something Journal-y here',
                 'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
                 'url' => 'http://smith.com/cumque-sint-molestiae-minima-corporis-quaerat.html',
                 'datasets' => [
                     0 => [
                         'id' => 1,
                         'link_type' => 'USING',
                     ],
                 ],
                 'status' => 'ACTIVE'
             ],
            $this->header,
        );

        $responseUpdate->assertStatus(200);
        $responseUpdate->assertJsonStructure([
            'message',
            'data'
        ]);

        $content = $responseUpdate->decodeResponseJson()['data'];
        $this->assertEquals($content['paper_title'], 'Not A Test Paper Title');
    }

    public function test_v2_patch_publication_status_by_team_id_with_success(): void
    {
        // draft to archived
        $publicationStatusDraft = Publication::where('status', Publication::STATUS_DRAFT)->first();
        $countStatusDraft = Publication::where('status', Publication::STATUS_DRAFT)->get()->count();
        $response = $this->json(
            'PATCH',
            '/api/v2/teams/' . $publicationStatusDraft->team_id . '/publications/' . $publicationStatusDraft->id,
            [
                'status' => Publication::STATUS_ARCHIVED
            ],
            $this->header,
        );
        $response->assertStatus(200);
        $countAfter = Publication::where('status', Publication::STATUS_DRAFT)->get()->count();
        $this->assertTrue(($countStatusDraft - $countAfter) === 1);


        // draft to active
        $countActiveBefore = Publication::where("status", Publication::STATUS_ACTIVE)->count();
        $countDraftBefore = Publication::where("status", Publication::STATUS_DRAFT)->count();
        $publicationStatusDraft = Publication::where('status', Publication::STATUS_DRAFT)->first();
        $countArchivedBefore = Publication::where("status", Publication::STATUS_ARCHIVED)->count();

        $response = $this->json(
            'PATCH',
            '/api/v2/teams/' . $publicationStatusDraft->team_id . '/publications/' . $publicationStatusDraft->id,
            [
                'status' => 'ACTIVE'
            ],
            $this->header,
        );

        $response->assertStatus(200);
        $countActive = Publication::where("status", Publication::STATUS_ACTIVE)->count();
        $countDraft = Publication::where("status", Publication::STATUS_DRAFT)->count();
        $countArchived = Publication::where("status", Publication::STATUS_ARCHIVED)->count();
        $this->assertTrue(($countActive - 1) === $countActiveBefore);
        $this->assertTrue($countArchived === $countArchivedBefore);
        $this->assertTrue(($countDraft + 1) === $countDraftBefore);

    }

    public function test_v2_patch_publication_status_by_user_id_with_success(): void
    {
        // draft to archived
        $publicationStatusDraft = Publication::where('status', Publication::STATUS_DRAFT)->first();
        $countStatusDraft = Publication::where('status', Publication::STATUS_DRAFT)->get()->count();
        $response = $this->json(
            'PATCH',
            '/api/v2/users/' . $publicationStatusDraft->owner_id . '/publications/' . $publicationStatusDraft->id,
            [
                'status' => Publication::STATUS_ARCHIVED
            ],
            $this->header,
        );
        $response->assertStatus(200);
        $countAfter = Publication::where('status', Publication::STATUS_DRAFT)->get()->count();
        $this->assertTrue(($countStatusDraft - $countAfter) === 1);


        // draft to active
        $countActiveBefore = Publication::where("status", Publication::STATUS_ACTIVE)->count();
        $countDraftBefore = Publication::where("status", Publication::STATUS_DRAFT)->count();
        $publicationStatusDraft = Publication::where('status', Publication::STATUS_DRAFT)->first();
        $countArchivedBefore = Publication::where("status", Publication::STATUS_ARCHIVED)->count();

        $response = $this->json(
            'PATCH',
            '/api/v2/users/' . $publicationStatusDraft->owner_id . '/publications/' . $publicationStatusDraft->id,
            [
                'status' => 'ACTIVE'
            ],
            $this->header,
        );

        $response->assertStatus(200);
        $countActive = Publication::where("status", Publication::STATUS_ACTIVE)->count();
        $countDraft = Publication::where("status", Publication::STATUS_DRAFT)->count();
        $countArchived = Publication::where("status", Publication::STATUS_ARCHIVED)->count();
        $this->assertTrue(($countActive - 1) === $countActiveBefore);
        $this->assertTrue($countArchived === $countArchivedBefore);
        $this->assertTrue(($countDraft + 1) === $countDraftBefore);

    }

    private function getTeamWithStatus($status)
    {
        $publication = Publication::where('status', strtoupper($status))->select(['id', 'team_id'])->first();
        return $publication->team_id;
    }

    private function getUserWithStatus($status)
    {
        $publication = Publication::where('status', strtoupper($status))->select(['id', 'owner_id'])->first();
        return $publication->owner_id;
    }

    private function generateTools()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Tool::all()->random()->id;
            $return[] = $temp;
        }

        return $return;
    }
}
