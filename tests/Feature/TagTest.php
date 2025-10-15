<?php

namespace Tests\Feature;

use App\Models\Tag;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class TagTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/tags';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * Get All Tags with success
     *
     * @return void
     */
    public function test_get_all_tags_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertJsonStructure([
            'current_page',
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
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'type' =>  'Tag-123456789',
                'enabled' => 1,
                'description' => 'type for test',
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
     * Create new Tag without success
     *
     * @return void
     */
    public function test_create_tag_without_success(): void
    {
        // create 1
        $responseCreate1 = $this->json(
            'POST',
            self::TEST_URL,
            [
                'type' =>  'Tag-123456789',
                'enabled' => 1,
                'description' => 'type for test'
            ],
            $this->header,
        );

        $responseCreate1->assertStatus(201);
        $responseCreate1->assertJsonStructure([
            'message',
            'data'
        ]);

        // create 2
        $responseCreate2 = $this->json(
            'POST',
            self::TEST_URL,
            [
                'type' =>  'Tag-123456789',
                'enabled' => 1,
                'description' => 'type for test'
            ],
            $this->header,
        );

        $responseCreate2->assertStatus(400);
        $responseCreate2->assertJsonStructure([
            'status',
            'message',
            'errors'
        ]);
    }

    /**
     * Update Tag with success
     *
     * @return void
     */
    public function test_update_tag_with_success(): void
    {
        $countBefore = Tag::all()->count();
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'type' =>  'Type Test',
                'enabled' => 1,
                'description' => 'type for test'
            ],
            $this->header,
        );

        $countAfterPost = Tag::all()->count();

        $response->assertStatus(201);
        $this->assertTrue((bool) ($countAfterPost - $countBefore), 'Response was successfully');
        $response->assertJsonStructure([
            'message',
            'data'
        ]);

        $tagId = (int) $response['data'];

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $tagId,
            [
                'type' =>  'Type Test Update',
                'enabled' => 1,
                'description' => 'type for test'
            ],
            $this->header,
        );
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
