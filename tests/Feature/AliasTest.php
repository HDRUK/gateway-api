<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Alias;
use Laravel\Pennant\Feature;
use Database\Seeders\AliasSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\MinimalUserSeeder;


class AliasTest extends TestCase
{
    
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = 'api/v1/aliases';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * List all Aliases
     *
     * @return void
     */
    public function test_the_application_can_list_aliases()
    {
        $response = $this->get('api/v1/aliases', $this->header);
        if (!Feature::active('Aliases')) {
            $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } else {
            $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
                ->assertJsonStructure([
                    'data' => [
                        0 => [
                            'id',
                            'name',
                        ],
                    ],
                    'current_page',
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
        }
    }

    /**
     * Tests that an activity log can be listed by id
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_alias()
    {
        $response = $this->json(
            'POST',
            'api/v1/aliases',
            [
                'name' => $this->getUniqueAlias(),
            ],
            $this->header,
        );

        if (!Feature::active('Aliases')) {
            $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } else {
            $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

            $content = $response->decodeResponseJson();

            $response = $this->get('api/v1/aliases/' . $content['data'], $this->header);

            $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                    ],
                ]);
        }
    }

    /**
     * Tests that an activity log can be created
     *
     * @return void
     */
    public function test_the_application_can_create_an_alias()
    {
        $response = $this->json(
            'POST',
            'api/v1/aliases',
            [
                'name' => $this->getUniqueAlias(),
            ],
            $this->header,
        );

        if (!Feature::active('Aliases')) {
            $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } else {
            $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
                ->assertJsonStructure([
                    'message',
                    'data',
                ]);

            $content = $response->decodeResponseJson();
            $this->assertEquals(
                $content['message'],
                Config::get('statuscodes.STATUS_CREATED.message')
            );
        }
    }

    /**
     * Tests it can update an activity log
     *
     * @return void
     */
    public function test_the_application_can_update_an_alias()
    {
        $response = $this->json(
            'POST',
            'api/v1/aliases',
            [
                'name' => $this->getUniqueAlias(),
            ],
            $this->header,
        );

        if (!Feature::active('Aliases')) {
            $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } else {
            $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
                ->assertJsonStructure([
                    'message',
                    'data',
                ]);

            $content = $response->decodeResponseJson();
            $this->assertEquals(
                $content['message'],
                Config::get('statuscodes.STATUS_CREATED.message')
            );

            $updateAlias = $this->getUniqueAlias();
            $response = $this->json(
                'PUT',
                'api/v1/aliases/' . $content['data'],
                [
                    'name' => $updateAlias,
                ],
                $this->header,
            );

            $content = $response->decodeResponseJson();

            $this->assertEquals($content['data']['name'], $updateAlias);
        }
    }

    /**
     * Tests it can delete an activity log
     *
     * @return void
     */
    public function test_it_can_delete_an_alias()
    {
        $response = $this->json(
            'POST',
            'api/v1/aliases',
            [
                'name' => $this->getUniqueAlias(),
            ],
            $this->header,
        );

        if (!Feature::active('Aliases')) {
            $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } else {
            $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
                ->assertJsonStructure([
                    'message',
                    'data',
                ]);

            $content = $response->decodeResponseJson();
            $this->assertEquals(
                $content['message'],
                Config::get('statuscodes.STATUS_CREATED.message')
            );

            // Finally, delete the last entered activity log to
            // prove functionality
            $response = $this->json(
                'DELETE',
                'api/v1/aliases/' . $content['data'],
                [],
                $this->header,
            );

            $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
                ->assertJsonStructure([
                    'message',
                ]);

            $content = $response->decodeResponseJson();
            $this->assertEquals(
                $content['message'],
                Config::get('statuscodes.STATUS_OK.message')
            );
        }
    }

    private function getUniqueAlias()
    {
        do {
            $alias = fake()->unique()->word();
            $checkAlias = Alias::where('name', $alias)->first();
        } while (!is_null($checkAlias));

        return $alias;
    }
}
