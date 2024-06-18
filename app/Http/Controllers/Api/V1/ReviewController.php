<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Requests\Review\GetReview;
use App\Http\Requests\Review\EditReview;
use App\Http\Requests\Review\CreateReview;
use App\Http\Requests\Review\DeleteReview;
use App\Http\Requests\Review\UpdateReview;
use App\Http\Traits\RequestTransformation;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use RequestTransformation;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/reviews",
     *    operationId="fetch_all_reviews",
     *    tags={"Reviews"},
     *    summary="ReviewController@index",
     *    description="Get All Reviews",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(
     *               property="data", 
     *               type="array",
     *               @OA\Items(type="object", 
     *                  @OA\Property(property="id", type="integer", example="1"),
     *                  @OA\Property(property="tool_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="rating", type="integer", example="1"),
     *                  @OA\Property(property="review_text", type="string", example="Laudantium fugit veniam iste."),
     *                  @OA\Property(property="review_state", type="integer", example="active"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="tool", type="object", 
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="mongo_object_id", type="string", example="ce37b00y7eiux03cca09pr0u"),
     *                     @OA\Property(property="name", type="string", example="Vel id iure aut qui quia rerum."),
     *                     @OA\Property(property="url", type="string", example="https://www.dickens.com/maiores-a-qui-laborum-reiciendis-necessitatibus-sed-non"),
     *                     @OA\Property(property="description", type="string", example="Sit quisquam est recusandae."),
     *                     @OA\Property(property="license", type="string", example="Inventore dolor quis magnam qui."),
     *                     @OA\Property(property="tech_stack", type="string", example="Inventore dolor quis magnam qui."),
     *                     @OA\Property(property="user_id", type="integer", example="1"),
     *                     @OA\Property(property="enabled", type="boolean", example="false"),
     *                     @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                     @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                     @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  ),
     *                  @OA\Property(property="user", type="object", 
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Rocio Mayer"),
     *                     @OA\Property(property="firstname", type="string", example="something or null"),
     *                     @OA\Property(property="lastname", type="string", example="something or null"),
     *                     @OA\Property(property="email", type="string", example="stanton.sibyl@example.net"),
     *                     @OA\Property(property="email_verified_at", type="integer", example="2023-05-18T01:25:00.000000Z"),
     *                     @OA\Property(property="providerid", type="string", example="something or null"),
     *                     @OA\Property(property="provider", type="string", example="something or null"),
     *                     @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                     @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                     @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  ),
     *               ),
     *            ),
     *         ),
     *      ),
     *    ),
     * )
     */

    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $reviews = Review::with(['tool', 'user'])->paginate(Config::get('constants.per_page'), ['*'], 'page');

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Review get all",
            ]);

            return response()->json(
                $reviews
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/reviews/{id}",
     *    operationId="fetch_reviews",
     *    tags={"Reviews"},
     *    summary="ReviewController@show",
     *    description="Get review by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="review id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="review id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data", 
     *             type="array",
     *             @OA\Items(type="object", 
     *                @OA\Property(property="id", type="integer", example="1"),
     *                @OA\Property(property="tool_id", type="integer", example="1"),
     *                @OA\Property(property="user_id", type="integer", example="1"),
     *                @OA\Property(property="rating", type="integer", example="1"),
     *                @OA\Property(property="review_text", type="string", example="Laudantium fugit veniam iste."),
     *                @OA\Property(property="review_state", type="integer", example="active"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="tool", type="object", 
     *                   @OA\Property(property="id", type="integer", example="1"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="ce37b00y7eiux03cca09pr0u"),
     *                   @OA\Property(property="name", type="string", example="Vel id iure aut qui quia rerum."),
     *                   @OA\Property(property="url", type="string", example="https://www.dickens.com/maiores-a-qui-laborum-reiciendis-necessitatibus-sed-non"),
     *                   @OA\Property(property="description", type="string", example="Sit quisquam est recusandae."),
     *                   @OA\Property(property="license", type="string", example="Inventore dolor quis magnam qui."),
     *                   @OA\Property(property="tech_stack", type="string", example="Inventore dolor quis magnam qui."),
     *                   @OA\Property(property="user_id", type="integer", example="1"),
     *                   @OA\Property(property="enabled", type="boolean", example="false"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                ),
     *                @OA\Property(property="user", type="object", 
     *                   @OA\Property(property="id", type="integer", example="1"),
     *                   @OA\Property(property="name", type="string", example="Rocio Mayer"),
     *                   @OA\Property(property="firstname", type="string", example="something or null"),
     *                   @OA\Property(property="lastname", type="string", example="something or null"),
     *                   @OA\Property(property="email", type="string", example="stanton.sibyl@example.net"),
     *                   @OA\Property(property="email_verified_at", type="integer", example="2023-05-18T01:25:00.000000Z"),
     *                   @OA\Property(property="providerid", type="string", example="something or null"),
     *                   @OA\Property(property="provider", type="string", example="something or null"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       ),
     *    ),
     * )
     */
    public function show(GetReview $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $reviews = Review::with(['tool', 'user'])
                        ->where(['id' => $id])
                        ->get();

            if ($reviews->count()) {
                Auditor::log([
                    'user_id' => (int) $jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Review get " . $id,
                ]);

                return response()->json([
                    'message' => 'success',
                    'data' => $reviews,
                ], 200);
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/reviews",
     *    operationId="create_reviews",
     *    tags={"Reviews"},
     *    summary="ReviewController@store",
     *    description="Create a new review",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="tool_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *             @OA\Property(property="rating", type="integer", example="1"),
     *             @OA\Property(property="review_text", type="string", example="Similique provident natus facere eveniet facere. Cumque corporis et cumque consequatur."),
     *             @OA\Property(property="review_state", type="string", example="active"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=201,
     *       description="Created",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="data", type="integer", example="100")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error"),
     *       )
     *    )
     * )
     * 
     * Create a new review
     *
     * @param CreateReview $request
     * @return JsonResponse
     */
    public function store(CreateReview $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $review = Review::create([
                'tool_id' => (int) $input['tool_id'],
                'user_id' => (int) $input['user_id'],
                'rating' => (int) $input['rating'],
                'review_text' => $input['review_text'],
                'review_state' => $input['review_state'],
            ]);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Review " . $review->id . " created",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $review->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/reviews/{id}",
     *    tags={"Reviews"},
     *    summary="Update a review",
     *    description="Update a review",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="review id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="review id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="tool_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *             @OA\Property(property="rating", type="integer", example="1"),
     *             @OA\Property(property="review_text", type="string", example="Similique provident natus facere eveniet facere. Cumque corporis et cumque consequatur."),
     *             @OA\Property(property="review_state", type="string", example="active"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                  property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="tool_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="rating", type="integer", example="5"),
     *                  @OA\Property(property="review_text", type="string", example="Similique provident natus facere eveniet facere."),
     *                  @OA\Property(property="review_state", type="string", example="active"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *            ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     *
     * @param UpdateReview $request
     * @param integer $id
     * @return JsonResponse
     */
    public function update(UpdateReview $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            Review::where('id', $id)->update([
                'tool_id' => $input['tool_id'],
                'user_id' => $input['user_id'],
                'rating' => $input['rating'],
                'review_text' => $input['review_text'],
                'review_state' => $input['review_state'],
            ]);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Review " . $id . " updated",
            ]);

            return response()->json([
                'message' => 'success',
                'data' => Review::where('id', $id)->first()
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/reviews/{id}",
     *    tags={"Reviews"},
     *    summary="Edit a review",
     *    description="Edit a review",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="review id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="review id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="tool_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *             @OA\Property(property="rating", type="integer", example="1"),
     *             @OA\Property(property="review_text", type="string", example="Similique provident natus facere eveniet facere. Cumque corporis et cumque consequatur."),
     *             @OA\Property(property="review_state", type="string", example="active"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                  property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="tool_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="rating", type="integer", example="5"),
     *                  @OA\Property(property="review_text", type="string", example="Similique provident natus facere eveniet facere."),
     *                  @OA\Property(property="review_state", type="string", example="active"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *            ),
     *         ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     *
     * @param EditReview $request
     * @param integer $id
     * @return JsonResponse
     */
    public function edit(EditReview $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $arrayKeys = [
                'tool_id',
                'user_id',
                'rating',
                'review_text',
                'review_state',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Review::where('id', $id)->update($array);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Review " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Review::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/reviews/{id}",
     *    tags={"Reviews"},
     *    summary="Delete a review",
     *    description="Delete a review",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="review id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="review id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error")
     *       )
     *    )
     * )
     *
     * @param DeleteReview $request
     * @param integer $id
     * @return JsonResponse
     */
    public function destroy(DeleteReview $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $review = Review::findOrFail($id);
            if ($review) {
                $review->delete();

                Auditor::log([
                    'user_id' => (int) $jwtUser['id'],
                    'action_type' => 'DELETE',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Review " . $id . " deleted",
                ]);
    
                return response()->json([
                    'message' => 'success',
                ], 200);
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
