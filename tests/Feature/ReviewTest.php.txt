<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Review;
use App\Models\License;
use Database\Seeders\TagSeeder;
use Database\Seeders\ToolSeeder;
use Database\Seeders\ReviewSeeder;
use Database\Seeders\SectorSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TypeCategorySeeder;
use Illuminate\Foundation\Testing\WithFaker;

class ReviewTest extends TestCase
{
    use WithFaker;

    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/reviews';

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
            CategorySeeder::class,
            TypeCategorySeeder::class,
            TagSeeder::class,
            LicenseSeeder::class,
            ToolSeeder::class,
            ReviewSeeder::class,
            SectorSeeder::class,
        ]);
    }

    /**
     * Get All Reviews with success
     *
     * @return void
     */
    public function test_get_all_reviews_with_success(): void
    {
        // review
        $countReview = Review::with(['tools', 'users'])->count();
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $this->assertCount($countReview, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'tool_id',
                    'user_id',
                    'rating',
                    'review_text',
                    'review_state',
                    'created_at',
                    'updated_at',
                    'created_at',
                    'deleted_at',
                    'tool',
                    'user',
                ]
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
        $response->assertStatus(200);
    }

    /**
     * Get Review by Id with success
     *
     * @return void
     */
    public function test_get_review_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'tool_id',
                    'user_id',
                    'rating',
                    'review_text',
                    'review_state',
                    'created_at',
                    'updated_at',
                    'created_at',
                    'deleted_at',
                    'tool',
                    'user',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Review with success
     *
     * @return void
     */
    public function test_add_new_review_with_success(): void
    {
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        // tool
        $responseTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                "mongo_object_id" => "5ece82082abda8b3a06f1941",
                "name" => "Similique sapiente est vero eum.",
                "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
                "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
                "license" => $licenseId,
                "tech_stack" => "Cumque molestias excepturi quam at.",
                "category_id" => 1,
                "user_id" => 1,
                "tag" => array(1, 2),
                "enabled" => 1,
            ],
            $this->header
        );
        $responseTool->assertStatus(201);

        // user
        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseUser->assertStatus(201);

        // new review
        $newReviewData =  [
            "tool_id" => $responseTool['data'],
            "user_id" => $responseUser['data'],
            "rating" => 4,
            "review_text" => htmlentities(implode(" ", $this->faker->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            "review_state" => "active",
        ];
        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $newReviewData,
            $this->header
        );

        $existsReview = Review::where($newReviewData)
                        ->get()
                        ->toArray();

        $this->assertTrue((bool) count($existsReview), 'Response was successfully');
        $response->assertStatus(201);
    }

    /**
     * Update Review with success by id and generate an exception
     *
     * @return void
     */
    public function test_update_review_with_success(): void
    {
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        // tool
        $responseTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                "mongo_object_id" => "5ece82082abda8b3a06f1941",
                "name" => "Similique sapiente est vero eum.",
                "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
                "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
                "license" => $licenseId,
                "tech_stack" => "Cumque molestias excepturi quam at.",
                "category_id" => 1,
                "user_id" => 1,
                "tag" => array(1, 2),
                "enabled" => 1,
            ],
            $this->header
        );
        $responseTool->assertStatus(201);

        // user
        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 12345657,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseUser->assertStatus(201);

        // new review
        $newReviewData =  [
            "tool_id" => $responseTool['data'],
            "user_id" => $responseUser['data'],
            "rating" => 4,
            "review_text" => htmlentities(implode(" ", $this->faker->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            "review_state" => "active",
        ];
        $responseNewReview = $this->json(
            'POST',
            self::TEST_URL . '/',
            $newReviewData,
            $this->header
        );

        $existsReview = Review::where($newReviewData)
            ->get()
            ->toArray();

        $this->assertTrue((bool) count($existsReview), 'Response was successfully');
        $responseNewReview->assertStatus(201);

        $newReviewId = (int) $responseNewReview['data'];

        // update review by id
        $updateReviewData =  [
            "tool_id" => $responseTool['data'],
            "user_id" => $responseUser['data'],
            "rating" => 4,
            "review_text" => htmlentities(implode(" ", $this->faker->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            "review_state" => "active",
        ];
        $responseUpdateReview = $this->json(
            'PUT',
            self::TEST_URL . '/' . $newReviewId,
            $updateReviewData,
            $this->header
        );

        $existsUpdateReview = Review::where($updateReviewData)
            ->get()
            ->toArray();

        $this->assertTrue((bool) count($existsUpdateReview), 'Response was successfully');
        $responseUpdateReview->assertStatus(200);
    }

    /**
     * Edit Review with success by id and generate an exception
     *
     * @return void
     */
    public function test_edit_review_with_success(): void
    {
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        // tool
        $responseTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                "mongo_object_id" => "5ece82082abda8b3a06f1941",
                "name" => "Similique sapiente est vero eum.",
                "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
                "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
                "license" => $licenseId,
                "tech_stack" => "Cumque molestias excepturi quam at.",
                "category_id" => 1,
                "user_id" => 1,
                "tag" => array(1, 2),
                "enabled" => 1,
            ],
            $this->header
        );
        $responseTool->assertStatus(201);

        // user
        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseUser->assertStatus(201);

        // new review
        $newReviewData =  [
            "tool_id" => $responseTool['data'],
            "user_id" => $responseUser['data'],
            "rating" => 4,
            "review_text" => htmlentities(implode(" ", $this->faker->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            "review_state" => "active",
        ];
        $responseNewReview = $this->json(
            'POST',
            self::TEST_URL . '/',
            $newReviewData,
            $this->header
        );

        $existsReview = Review::where($newReviewData)
            ->get()
            ->toArray();

        $this->assertTrue((bool) count($existsReview), 'Response was successfully');
        $responseNewReview->assertStatus(201);

        $newReviewId = (int) $responseNewReview['data'];

        // update review by id
        $updateReviewData =  [
            "tool_id" => $responseTool['data'],
            "user_id" => $responseUser['data'],
            "rating" => 4,
            "review_text" => htmlentities(implode(" ", $this->faker->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            "review_state" => "active",
        ];
        $responseUpdateReview = $this->json(
            'PUT',
            self::TEST_URL . '/' . $newReviewId,
            $updateReviewData,
            $this->header
        );

        $existsUpdateReview = Review::where($updateReviewData)
            ->get()
            ->toArray();

        $this->assertTrue((bool) count($existsUpdateReview), 'Response was successfully');
        $responseUpdateReview->assertStatus(200);

        // edit
        $editReviewText = htmlentities(implode(" ", $this->faker->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8");
        $editReviewData =  [
            "tool_id" => $responseTool['data'],
            "user_id" => $responseUser['data'],
            "rating" => 3,
            "review_text" => $editReviewText,
            "review_state" => "rejected",
        ];
        $responseEditReview = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $newReviewId,
            $editReviewData,
            $this->header
        );

        $existsEditReview = Review::where($editReviewData)
            ->get()
            ->toArray();

        $this->assertTrue((bool) count($existsEditReview), 'Response was successfully');
        $responseEditReview->assertStatus(200);

        // edit
        $editReviewData2 =  [
            "rating" => 5,
            "review_text" => $editReviewText,
            "review_state" => "active",
        ];
        $responseEditReview2 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $newReviewId,
            $editReviewData2,
            $this->header
        );

        $existsEditReview2 = Review::where([
            "tool_id" => $responseTool['data'],
            "user_id" => $responseUser['data'],
            "rating" => 5,
            "review_text" => $editReviewText,
            "review_state" => "active",
        ])
            ->get()
            ->toArray();

        $this->assertTrue((bool) count($existsEditReview2), 'Response was successfully');
        $responseEditReview2->assertStatus(200);
    }

    /**
     * SoftDelete Review by Id with success
     *
     * @return void
     */
    public function test_soft_delete_review_with_success(): void
    {
        $countBefore = Review::onlyTrashed()->count();

        $licenseId = License::where('valid_until', null)->get()->random()->id;
        // tool
        $responseTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                "mongo_object_id" => "5ece82082abda8b3a06f1941",
                "name" => "Similique sapiente est vero eum.",
                "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
                "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
                "license" => $licenseId,
                "tech_stack" => "Cumque molestias excepturi quam at.",
                "category_id" => 1,
                "user_id" => 1,
                "tag" => array(1, 2),
                "enabled" => 1,
            ],
            $this->header
        );
        $responseTool->assertStatus(201);

        // user
        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'Just',
                'lastname' => 'Test',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseUser->assertStatus(201);

        // new review
        $newReviewData =  [
            "tool_id" => $responseTool['data'],
            "user_id" => $responseUser['data'],
            "rating" => 4,
            "review_text" => htmlentities(implode(" ", $this->faker->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            "review_state" => "active",
        ];
        $responseNewReview = $this->json(
            'POST',
            self::TEST_URL . '/',
            $newReviewData,
            $this->header
        );

        $existsReview = Review::where($newReviewData)
            ->get()
            ->toArray();

        $this->assertTrue((bool) count($existsReview), 'Response was successfully');
        $responseNewReview->assertStatus(201);

        $newReviewId = (int) $responseNewReview['data'];

        // delete review
        $responseDeleteReview = $this->json('DELETE', self::TEST_URL . '/' . $newReviewId, [], $this->header);
        $countAfter = Review::onlyTrashed()->count();

        $responseDeleteReview->assertStatus(200);

        $this->assertEquals(
            $countBefore + 1,
            $countAfter,
            "actual value is equals to expected"
        );
        $this->assertTrue((bool) ($countAfter - $countBefore), 'Response was successfully');
    }
}
