<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Http;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TeamHasUserSeeder;

class SearchTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL_SEARCH = '/api/v1/search';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            TeamHasUserSeeder::class,
            DatasetSeeder::class,
        ]);
    }

    /**
     * Search using a query with success
     * 
     * @return void
     */
    public function test_search_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL_SEARCH, ["query" => "asthma"], $this->header);   
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'datasets',
                'collections',
                'tools'
            ]
        ]);

        $response = $this->json('GET', self::TEST_URL_SEARCH . '?sort=score:asc', ["query" => "asthma"], $this->header);   
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'datasets',
                'collections',
                'tools'
            ]
        ]);
        $this->assertTrue($response['data']['datasets'][0]['_source']['shortTitle'] === 'Third asthma dataset');

        // Test sorting by dataset name (shortTitle)        
        $response = $this->json('GET', self::TEST_URL_SEARCH . '?sort=title:asc', ["query" => "asthma"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'datasets',
                'collections',
                'tools'
            ]
        ]);
        $this->assertTrue($response['data']['datasets'][0]['_source']['shortTitle'] === 'Another asthma dataset');

        // Test sorting by created_at desc        
        $response = $this->json('GET', self::TEST_URL_SEARCH . '?sort=created_at:desc', ["query" => "asthma"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'datasets',
                'collections',
                'tools'
            ]
        ]);
        $this->assertTrue($response['data']['datasets'][0]['_id'] === '1');
    }
}