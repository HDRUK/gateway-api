<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataAccessApplicationReview\CreateDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\DeleteDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\GetDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\UpdateDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\UpdateUserDataAccessApplicationReview;
use App\Http\Traits\RequestTransformation;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationComment;
use App\Models\DataAccessApplicationReview;

class DataAccessApplicationReviewController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/dar/applications/{id}/reviews",
     *      summary="Return all reviews on a DAR application",
     *      description="Return all reviews on a DAR application",
     *      tags={"DataAccessApplicationReview"},
     *      summary="DataAccessApplicationReview@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="application_id", type="integer", example="1"),
     *                  @OA\Property(property="question_id", type="integer", example="1"),
     *                  @OA\Property(property="comments", type="array", @OA\Items(
     *
     *
     *                  )),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function index(GetDataAccessApplicationReview $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $reviews = DataAccessApplicationReview::where('application_id', $id)
                ->with('comments')
                ->get();

            if ($reviews) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataAccessApplicationReview get ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $reviews,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/dar/applications/{id}/questions/{questionId}/reviews",
     *      summary="Create a new review comment on a question in a DAR application",
     *      description="Create a new review comment on a question in a DAR application",
     *      tags={"DataAccessApplicationReview"},
     *      summary="DataAccessApplicationReview@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="questionId",
     *         in="path",
     *         description="DAR application question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application question id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessApplicationReview definition",
     *          @OA\JsonContent(
     *              required={"comment"},
     *              @OA\Property(property="comment", type="string", example="A review of this application"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="team_id", type="integer", example="1"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example="100")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function store(CreateDataAccessApplicationReview $request, int $id, int $questionId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $review = DataAccessApplicationReview::create([
                'application_id' => $id,
                'question_id' => $questionId,
            ]);

            DataAccessApplicationComment::create([
                'review_id' => $review->id,
                'user_id' => $input['user_id'] ?? null,
                'team_id' => $input['team_id'] ?? null,
                'comment' => $input['comment'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplicationReview ' . $review->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $review->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/dar/applications/{id}/questions/{questionId}/reviews/{reviewId}",
     *      summary="Update a review comment on a question in a DAR application",
     *      description="Update a review comment on a question in a DAR application",
     *      tags={"DataAccessApplicationReview"},
     *      summary="DataAccessApplicationReview@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="questionId",
     *         in="path",
     *         description="DAR application question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application question id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="reviewId",
     *         in="path",
     *         description="DAR application review id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application review id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessApplicationReview definition",
     *          @OA\JsonContent(
     *              required={"comment"},
     *              @OA\Property(property="comment", type="string", example="A review of this application"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="team_id", type="integer", example="1"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="application_id", type="integer", example="1"),
     *                  @OA\Property(property="question_id", type="integer", example="1"),
     *                  @OA\Property(property="comments", type="array", @OA\Items()),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function update(UpdateDataAccessApplicationReview $request, int $id, int $questionId, int $reviewId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $review = DataAccessApplicationReview::findOrFail($reviewId);

            DataAccessApplicationComment::create([
                'review_id' => $review->id,
                'user_id' => null,
                'team_id' => $input['team_id'] ?? null,
                'comment' => $input['comment'],
            ]);

            if (isset($input['resolved'])) {
                $review->update(['resolved' => $input['resolved']]);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplicationReview ' . $reviewId . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessApplicationReview::where('id', $reviewId)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/users/{userId}/dar/applications/{id}/questions/{questionId}/reviews/{reviewId}",
     *      summary="User endpoint to update a review comment on a question in a DAR application",
     *      description="User endpoint to update a review comment on a question in a DAR application",
     *      tags={"DataAccessApplicationReview"},
     *      summary="DataAccessApplicationReview@userUpdate",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="User id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="questionId",
     *         in="path",
     *         description="DAR application question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application question id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="reviewId",
     *         in="path",
     *         description="DAR application review id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application review id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessApplicationReview definition",
     *          @OA\JsonContent(
     *              required={"comment"},
     *              @OA\Property(property="comment", type="string", example="A review of this application"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="application_id", type="integer", example="1"),
     *                  @OA\Property(property="question_id", type="integer", example="1"),
     *                  @OA\Property(property="comments", type="array", @OA\Items()),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function userUpdate(UpdateUserDataAccessApplicationReview $request, int $userId, int $id, int $questionId, int $reviewId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $application = DataAccessApplication::findOrFail($id);
            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to review this application.');
            }

            $review = DataAccessApplicationReview::findOrFail($reviewId);

            DataAccessApplicationComment::create([
                'review_id' => $review->id,
                'user_id' => $userId,
                'comment' => $input['comment'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplicationReview ' . $reviewId . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessApplicationReview::where('id', $reviewId)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }


    // TODO PATCH method if/when more fields are added to the DataAccessApplicationReview model
    // But currently only one field is editable so PUT is sufficient.

    /**
     * @OA\Delete(
     *      path="/api/v1/dar/applications/{id}/questions/{questionId}/reviews/{reviewId}",
     *      summary="Delete a review from a DAR application",
     *      description="Delete a review from a DAR application",
     *      summary="DataAccessApplicationReview@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="questionId",
     *         in="path",
     *         description="DAR application question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application question id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="reviewId",
     *         in="path",
     *         description="DAR application review id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application review id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *           ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function destroy(DeleteDataAccessApplicationReview $request, int $id, int $questionId, int $reviewId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $review = DataAccessApplicationReview::findOrFail($reviewId);

            DataAccessApplicationComment::where('review_id', $review->id)->delete();

            $review->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplicationReview ' . $reviewId . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
