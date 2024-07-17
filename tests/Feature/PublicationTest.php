<?php

namespace Tests\Feature;
use Exception;
use Config;
use Tests\TestCase;
use App\Models\Tool;
use App\Models\Publication;
use Database\Seeders\TagSeeder;
use Tests\Traits\Authorization;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\LicenseSeeder;
use App\Models\PublicationHasDataset;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\TypeCategorySeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\PublicationHasToolSeeder;
use Database\Seeders\PublicationHasDatasetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL = '/api/v1/publications';

    protected $header = [];

    /**
     * Set up the databse
     * 
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            TeamHasUserSeeder::class,
            PublicationSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            PublicationHasDatasetSeeder::class,
            LicenseSeeder::class,
            ToolSeeder::class,
            TagSeeder::class,
            TypeCategorySeeder::class,
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
                'datasets',
                'tools',
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
        $elasticCountBefore = $this->countElasticClientRequests($this->testElasticClient);

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
                        'link_type' => 'USING',
                    ],
                ],
                'tools' => $this->generateTools(),
            ],
            $this->header,
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data'
        ]);

        $pubId = $response->decodeResponseJson()['data'];
        $relation = PublicationHasDataset::where('publication_id', $pubId)->first();
        $this->assertNotNull($relation);
        $this->assertEquals($relation['link_type'], "USING");

        $elasticCountAfter = $this->countElasticClientRequests($this->testElasticClient);
        $this->assertTrue($elasticCountAfter > $elasticCountBefore);
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

    private function generateTools()
    {
        $tools = Tool::all();
        if ($tools->isEmpty()) {
            throw new Exception('No tools available to generate.');
        }

        $return = [];
        $iterations = rand(1, min(5, $tools->count()));

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = $tools->random()->id;
            $temp['reason'] = htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8");
            $return[] = $temp;
        }

        return $return;
    }
}
