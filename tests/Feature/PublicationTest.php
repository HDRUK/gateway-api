<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\DatasetVersion;
use Database\Seeders\TagSeeder;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\TeamHasUserSeeder;

use Database\Seeders\TypeCategorySeeder;

use Database\Seeders\DatasetVersionSeeder;

use App\Models\PublicationHasDatasetVersion;
use Database\Seeders\PublicationHasToolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\PublicationHasDatasetVersionSeeder;

class PublicationTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/publications';

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

        $this->seed([
            MinimalUserSeeder::class,
            TeamHasUserSeeder::class,
            PublicationSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            PublicationHasDatasetVersionSeeder::class,
            LicenseSeeder::class,
            CategorySeeder::class,
            TypeCategorySeeder::class,
            ToolSeeder::class,
            TagSeeder::class,
            PublicationHasToolSeeder::class,
        ]);
    }

    /**
     * Get all publications with success
     *
     * @return void
     */
    public function test_get_all_publications_with_success(): void
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
    public function test_get_publication_by_id_with_success(): void
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

    /**
     * Create new Publication with success
     *
     * @return void
     */
    public function test_create_publication_with_success(): void
    {
        $datasetId = 1;

        $response = $this->json(
            'POST',
            self::TEST_URL,
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

    /**
     * Create a new publication without success
     *
     * @return void
     */
    public function test_create_publication_without_success(): void
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                // omit paper title which is a required field
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
                        'link_type' => 'USING',
                    ],
                ],
                'tools' => $this->generateTools(),
            ],
            $this->header,
        );

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'status',
            'message',
            'errors',
        ]);
    }

    /**
     * Update a publication with success
     *
     * @return void
     */
    public function test_update_active_publication_with_success(): void
    {
        $countBefore = Publication::all()->count();
        $response = $this->json(
            'POST',
            self::TEST_URL,
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
            self::TEST_URL . '/' . $publicationId,
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

    public function test_can_change_active_publication_with_success(): void
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
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

        $publicationId = (int)$response['data'];

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $publicationId,
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
                'status' => 'DRAFT'
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(200);

    }

    public function test_can_count_with_success(): void
    {
        Publication::factory(1)->create(['status' => 'DRAFT']);

        $responseCount = $this->json(
            'GET',
            self::TEST_URL .
            '/count/status',
            [],
            $this->header
        );
        $responseCount->assertStatus(200);
        $countDraftDb = Publication::where('status', Publication::STATUS_DRAFT)->withTrashed()->get()->count();
        $countDraft = $responseCount['data']['DRAFT'];
        $this->assertTrue($countDraft === $countDraftDb);


        Publication::factory(1)->create(['status' => 'ACTIVE']);

        $responseCount = $this->json(
            'GET',
            self::TEST_URL .
            '/count/status',
            [],
            $this->header
        );
        $responseCount->assertStatus(200);
        $countActiveDb = Publication::where('status', Publication::STATUS_ACTIVE)->withTrashed()->get()->count();
        $countActive = $responseCount['data']['ACTIVE'];
        $this->assertTrue($countActive === $countActiveDb);

        //now delete one
        $response = $this->json('DELETE', self::TEST_URL . '/1', [], $this->header);
        $response->assertStatus(200);

        $responseCount = $this->json(
            'GET',
            self::TEST_URL .
            '/count/status',
            [],
            $this->header
        );
        $responseCount->assertStatus(200);
        $countArchivedDb = Publication::where('status', Publication::STATUS_ARCHIVED)->withTrashed()->get()->count();
        $countArchived = $responseCount['data']['ARCHIVED'];
        $this->assertTrue($countArchived === $countArchivedDb);

        $ownerId = 1;
        Publication::take(5)->update(['owner_id' => $ownerId]);

        $responseCount = $this->json(
            'GET',
            self::TEST_URL .
            '/count/status?owner_id='. $ownerId,
            [],
            $this->header
        );
        $responseCount->assertStatus(200);
        $countDraftDb2 = Publication::where([
            'status' => Publication::STATUS_DRAFT,
            'owner_id' => $ownerId,
            ])->get()->count();
        $countDraft = $responseCount['data']['DRAFT'] ?? 0;
        $this->assertTrue($countDraft === $countDraftDb2);

    }

    public function test_patch_publication_status_with_success(): void
    {
        // draft to archived
        $publicationStatusDraft = Publication::where('status', Publication::STATUS_DRAFT)->first();
        $countStatusDraft = Publication::where('status', Publication::STATUS_DRAFT)->get()->count();
        $response = $this->json(
            'PATCH',
            self::TEST_URL . "/" . $publicationStatusDraft->id,
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
        $countArchivedBefore = Publication::withTrashed()->where("status", Publication::STATUS_ARCHIVED)->count();

        $response = $this->json(
            'PATCH',
            self::TEST_URL . "/" . $publicationStatusDraft->id,
            [
                'status' => 'ACTIVE'
            ],
            $this->header,
        );

        $response->assertStatus(200);
        $countActive = Publication::where("status", Publication::STATUS_ACTIVE)->count();
        $countDraft = Publication::where("status", Publication::STATUS_DRAFT)->count();
        $countArchived = Publication::withTrashed()->where("status", Publication::STATUS_ARCHIVED)->count();
        $this->assertTrue(($countActive - 1) === $countActiveBefore);
        $this->assertTrue($countArchived === $countArchivedBefore);
        $this->assertTrue(($countDraft + 1) === $countDraftBefore);

    }

    public function test_can_filter_publications_not_by_status(): void
    {
        $firstPublicationTitle = Publication::where("id", 1)->get()->first()->paper_title;
        $response = $this->json(
            'GET',
            self::TEST_URL . "?paper_title=" . $firstPublicationTitle,
            $this->header,
        );

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);

    }

    public function test_can_filter_publications_on_status(): void
    {
        //all seeded publications are draft
        $countDraft = Publication::where("status", Publication::STATUS_DRAFT)->count();
        $response = $this->json(
            'GET',
            self::TEST_URL . "?status=DRAFT",
            $this->header,
        );
        //check adding a status filter doesnt crash
        $response->assertStatus(200);
        $this->assertCount($countDraft, $response['data']);

        //change one of the publications to active
        $response = $this->json(
            'PATCH',
            self::TEST_URL . "/1",
            [
                'status' => 'ACTIVE'
            ],
            $this->header,
        );
        $response->assertStatus(200);

        //filter to find this one active publciation
        $countActive = Publication::where("status", Publication::STATUS_ACTIVE)->count();
        $response = $this->json(
            'GET',
            self::TEST_URL . "?status=ACTIVE",
            $this->header,
        );
        $response->assertStatus(200);
        $this->assertCount($countActive, $response['data']);


        //update 2 other publications to be archived
        $response = $this->json(
            'PATCH',
            self::TEST_URL . "/2",
            [
                'status' => 'ARCHIVED'
            ],
            $this->header,
        );
        $response->assertStatus(200);

        $response = $this->json(
            'PATCH',
            self::TEST_URL . "/3",
            [
                'status' => 'ARCHIVED'
            ],
            $this->header,
        );
        $response->assertStatus(200);

        //check archived filter works
        $response = $this->json(
            'GET',
            self::TEST_URL . "?status=ARCHIVED",
            $this->header,
        );
        $response->assertStatus(200);
        $countArchived = Publication::where("status", Publication::STATUS_ARCHIVED)->withTrashed()->count();
        $this->assertCount($countArchived, $response['data']);
    }

    /**
     * SoftDelete Publication by id with success, and unarchive with success
     */
    public function test_soft_delete_and_unarchive_publication_with_success(): void
    {
        // Create a new publication for this test with ACTIVE status
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'paper_title' => 'Test Paper Title',
                'authors' => 'Einstein, Albert, Yankovich, Al',
                'year_of_publication' => '2013',
                'paper_doi' => '10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
                'url' => 'http://smith.com/cumque-sint-molestiae-minima-corporis-quaerat.html',
                'status' => 'ACTIVE'
            ],
            $this->header,
        );

        $response->assertStatus(201);
        $id = $response->decodeResponseJson()['data'];

        $countBefore = Publication::count();

        $response = $this->json('DELETE', self::TEST_URL . '/' . $id, [], $this->header);
        $response->assertStatus(200);

        $countTrashed = Publication::onlyTrashed()->count();
        $countAfter = Publication::count();

        $this->assertTrue($countTrashed === 1);
        $this->assertTrue($countAfter < $countBefore);

        $response = $this->json('PATCH', self::TEST_URL . '/' . $id . '?unarchive', ['status' => 'DRAFT'], $this->header);
        $response->assertStatus(200);

        $countTrashedAfterUnarchiving = Publication::onlyTrashed()->count();
        $countAfterUnarchiving = Publication::count();

        $this->assertEquals($countTrashedAfterUnarchiving, 0);
        $this->assertTrue($countAfter < $countAfterUnarchiving);
        $this->assertTrue($countBefore === $countAfterUnarchiving);
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
