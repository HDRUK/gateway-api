<?php

namespace Tests\Feature;

use App\Models\Tag;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL_TAG = '/api/v1/tags';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    /**
     * Get All Tags with success
     * 
     * @return void
     */
    public function test_get_all_tags_with_success(): void
    {
        $countTag = Tag::where('enabled', 1)->count();
        $response = $this->json('GET', self::TEST_URL_TAG, [], $this->header);

        $this->assertCount($countTag, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'type',
                    'description',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Get All Tag with no success
     * 
     * @return void
     */
    public function test_get_all_tags_and_generate_exception(): void
    {
        $response = $this->json('GET', self::TEST_URL_TAG, [], []);
        $response->assertStatus(401);
    }

    /**
     * Get Tag by Id with success
     * 
     * @return void
     */
    public function test_get_tag_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL_TAG . '/1', [], $this->header);

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'type',
                    'description',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * SoftDelete Tag by Id with success
     *
     * @return void
     */
    public function test_soft_delete_tag_with_success(): void
    {
        $id = 1;
        $countTag = Tag::where('id', $id)->count();
        $response = $this->json('DELETE', self::TEST_URL_TAG . '/' . $id, [], $this->header);

        $countTagDeleted = Tag::onlyTrashed()->where('id', $id)->count();

        $response->assertStatus(200);

        if ($countTag && $countTagDeleted) {
            $this->assertTrue(true, 'Response was successfully');
        } else {
            $this->assertTrue(false, 'Response was unsuccessfully');
        }
    }
}
