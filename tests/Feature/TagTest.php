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

    const TEST_URL = '/api/v1/tags';

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
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

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
        $response = $this->json('GET', self::TEST_URL, [], []);
        $response->assertStatus(401);
    }

    /**
     * Get Tag by Id with success
     * 
     * @return void
     */
    public function test_get_tag_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);

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
     * Create new Tag with success
     *
     * @return void
     */
    public function test_create_tag_with_success(): void
    {
        $countBefore = Tag::all()->count();

        $array = [
            'type' =>  'Type Test',
            'enabled' => 1,
            'description' => 'type for test'
        ];
        $response = $this->json('POST', self::TEST_URL, $array, $this->header);

        $countAfter = Tag::all()->count();

        $response->assertStatus(201);
        $this->assertTrue((bool) ($countAfter - $countBefore), 'Response was successfully');
        $response->assertJsonStructure([
            'message',
            'data'
        ]);
    }

    /**
     * Create new Tag without success
     *
     * @return void
     */
    public function test_create_tag_without_success(): void
    {
        $countBefore = Tag::all()->count();

        $array = [
            'type' =>  'Type Test',
            'enabled' => 1,
            'description' => 'type for test'
        ];
        $response = $this->json('POST', self::TEST_URL, $array, $this->header);

        $countAfter = Tag::all()->count();

        $response->assertStatus(201);
        $this->assertTrue((bool) ($countAfter - $countBefore), 'Response was successfully');
        $response->assertJsonStructure([
            'message',
            'data'
        ]);

        $responseRepeat = $this->json('POST', self::TEST_URL, $array, $this->header);
        $responseRepeat->assertStatus(500);
    }

    /**
     * Create new Tag without success
     *
     * @return void
     */
    public function test_update_tag_with_success(): void
    {
        $countBefore = Tag::all()->count();

        $array = [
            'type' =>  'Type Test',
            'enabled' => 1,
            'description' => 'type for test'
        ];
        $response = $this->json('POST', self::TEST_URL, $array, $this->header);

        $countAfterPost = Tag::all()->count();

        $response->assertStatus(201);
        $this->assertTrue((bool) ($countAfterPost - $countBefore), 'Response was successfully');
        $response->assertJsonStructure([
            'message',
            'data'
        ]);
        $tagId = (int) $response['data'];

        $arrayUpdate = [
            'type' =>  'Type Test Update',
            'enabled' => 1,
            'description' => 'type for test'
        ];
        $responseUpdate = $this->json('PUT', self::TEST_URL . '/' . $tagId, $arrayUpdate, $this->header);
        $countAfterUpdate = Tag::all()->count();

        $responseUpdate->assertStatus(200);
        $this->assertFalse((bool) ($countAfterUpdate - $countAfterPost), 'Response was successfully, No Id changed after update');
        $responseUpdate->assertJsonStructure([
            'message',
            'data'
        ]);
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
        $response = $this->json('DELETE', self::TEST_URL . '/' . $id, [], $this->header);

        $countTagDeleted = Tag::onlyTrashed()->where('id', $id)->count();

        $response->assertStatus(200);

        if ($countTag && $countTagDeleted) {
            $this->assertTrue(true, 'Response was successfully');
        } else {
            $this->assertTrue(false, 'Response was unsuccessfully');
        }
    }
}
