<?php

namespace Tests\Feature;

use Config;
use App\Models\Publication;
use Tests\TestCase;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\PublicationHasDatasetSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL = '/api/v1/publications';

    protected $header = [];

    /**
     * Set up the databse
     * 
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            TeamHasUserSeeder::class,
            PublicationSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            PublicationHasDatasetSeeder::class
        ]);
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
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

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
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
                ]
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
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'paper_title' => 'Test Paper Title',
                'authors' => 'Einstein, Albert, Yankovich, Al',
                'year_of_publication' => '2013',
                'paper_doi' => 'https://doi.org/10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
                'datasets' => [1,2],
            ],
            $this->header,
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data'
        ]);
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
                'paper_doi' => 'https://doi.org/10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',                
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
    public function test_update_publication_with_success(): void
    {
        $countBefore = Publication::all()->count();
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'paper_title' => 'Test Paper Title',
                'authors' => 'Einstein, Albert, Yankovich, Al',
                'year_of_publication' => '2013',
                'paper_doi' => 'https://doi.org/10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
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
                'paper_doi' => 'https://doi.org/10.1000/182',
                'publication_type' => 'Paper and such',
                'journal_name' => 'Something Journal-y here',
                'abstract' => 'Some blurb about this made up paper written by people who should never meet.',
            ],
            $this->header,
        );

        $content = $responseUpdate->decodeResponseJson()['data'];
        $this->assertEquals($content['paper_title'], 'Not A Test Paper Title');

        $responseUpdate->assertStatus(200);
        $responseUpdate->assertJsonStructure([
            'message',
            'data'
        ]);
    }

    /**
     * SoftDelete Publication by id with success
     */
    public function test_soft_delete_tag_with_success(): void
    {
        $countBefore = Publication::where('id', 1)->count();
        
        $response = $this->json('DELETE', self::TEST_URL . '/1', [], $this->header);
        $response->assertStatus(200);

        $countTrashed = Publication::onlyTrashed()->where('id', 1)->count();
        $countAfter = Publication::where('id', 1)->count();

        $this->assertTrue($countTrashed === 1);
        $this->assertTrue($countAfter < $countBefore);
    }
}
