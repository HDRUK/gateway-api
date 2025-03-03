<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataAccessApplicationReview\CreateDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\CreateGlobalDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\DeleteDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\DeleteGlobalDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\GetDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\GetUserDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\UpdateDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\UpdateGlobalDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\UpdateGlobalUserDataAccessApplicationReview;
use App\Http\Requests\DataAccessApplicationReview\UpdateUserDataAccessApplicationReview;
use App\Http\Traits\RequestTransformation;
use App\Http\Traits\DataAccessApplicationHelpers;
use App\Jobs\SendEmailJob;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationComment;
use App\Models\DataAccessApplicationReview;
use App\Models\EmailTemplate;
use App\Models\Team;
use App\Models\User;

class DataAccessApplicationReviewController extends Controller
{
    use RequestTransformation;
    use DataAccessApplicationHelpers;

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{team_id}/dar/applications/{id}/reviews",
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
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="user_id", type="integer", example="123"),
     *                      @OA\Property(property="team_id", type="integer", example="123"),
     *                      @OA\Property(property="review_id", type="integer", example="123"),
     *                      @OA\Property(property="comment", type="string", example="A comment"),
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
    public function index(GetDataAccessApplicationReview $request, int $teamId, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $this->checkTeamAccess($teamId, $id, 'view reviews on');

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
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     * @OA\Get(
     *      path="/api/v1/users/{userId}/dar/applications/{id}/reviews",
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
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="user_id", type="integer", example="123"),
     *                      @OA\Property(property="team_id", type="integer", example="123"),
     *                      @OA\Property(property="review_id", type="integer", example="123"),
     *                      @OA\Property(property="comment", type="string", example="A comment"),
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
    public function indexUser(GetUserDataAccessApplicationReview $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::findOrFail($id);
            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to review this application.');
            }

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
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     *      path="/api/v1/teams/{team_id}/dar/applications/{id}/questions/{questionId}/reviews",
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
    public function store(CreateDataAccessApplicationReview $request, int $teamId, int $id, int $questionId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $this->checkTeamAccess($teamId, $id, 'add reviews to');

            $review = DataAccessApplicationReview::create([
                'application_id' => $id,
                'question_id' => $questionId,
            ]);

            DataAccessApplicationComment::create([
                'review_id' => $review->id,
                'team_id' => $teamId,
                'comment' => $input['comment'],
            ]);

            $this->emailResearcherReview($review->id, $id);

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
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     *      path="/api/v1/teams/{team_id}/dar/applications/{id}/reviews",
     *      summary="Create a new review comment on a DAR application",
     *      description="Create a new review comment on a DAR application",
     *      tags={"DataAccessApplicationReview"},
     *      summary="DataAccessApplicationReview@storeGlobal",
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
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessApplicationReview definition",
     *          @OA\JsonContent(
     *              required={"comment"},
     *              @OA\Property(property="comment", type="string", example="A review of this application"),
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
    public function storeGlobal(CreateGlobalDataAccessApplicationReview $request, int $teamId, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $this->checkTeamAccess($teamId, $id, 'add reviews to');

            $review = DataAccessApplicationReview::create([
                'application_id' => $id,
            ]);

            DataAccessApplicationComment::create([
                'review_id' => $review->id,
                'team_id' => $teamId,
                'comment' => $input['comment'],
            ]);

            $this->emailResearcherReview($review->id, $id);

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
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     *      path="/api/v1/teams/{team_id}/dar/applications/{id}/questions/{questionId}/reviews/{reviewId}",
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
    public function update(UpdateDataAccessApplicationReview $request, int $teamId, int $id, int $questionId, int $reviewId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $this->checkTeamAccess($teamId, $id, 'add reviews to');

            $review = DataAccessApplicationReview::findOrFail($reviewId);

            DataAccessApplicationComment::create([
                'review_id' => $review->id,
                'team_id' => $teamId,
                'comment' => $input['comment'],
            ]);

            if (isset($input['resolved'])) {
                $review->update(['resolved' => $input['resolved']]);
            }

            $this->emailResearcherReview($review->id, $id);

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
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     *      path="/api/v1/teams/{team_id}/dar/applications/{id}/reviews/{reviewId}",
     *      summary="Update a review comment on a DAR application",
     *      description="Update a review comment on a DAR application",
     *      tags={"DataAccessApplicationReview"},
     *      summary="DataAccessApplicationReview@updateGlobal",
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
    public function updateGlobal(UpdateGlobalDataAccessApplicationReview $request, int $teamId, int $id, int $reviewId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $this->checkTeamAccess($teamId, $id, 'add reviews to');

            $review = DataAccessApplicationReview::findOrFail($reviewId);

            DataAccessApplicationComment::create([
                'review_id' => $review->id,
                'team_id' => $teamId,
                'comment' => $input['comment'],
            ]);

            if (isset($input['resolved'])) {
                $review->update(['resolved' => $input['resolved']]);
            }

            $this->emailResearcherReview($reviewId, $id);

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
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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

            $this->emailCustodianReview($reviewId, $id);

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
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     *      path="/api/v1/users/{userId}/dar/applications/{id}/reviews/{reviewId}",
     *      summary="User endpoint to update a review comment on a DAR application",
     *      description="User endpoint to update a review comment on a DAR application",
     *      tags={"DataAccessApplicationReview"},
     *      summary="DataAccessApplicationReview@userUpdateGlobal",
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
    public function userUpdateGlobal(UpdateGlobalUserDataAccessApplicationReview $request, int $userId, int $id, int $reviewId): JsonResponse
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

            $this->emailCustodianReview($reviewId, $id);

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
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     *      path="/api/v1/teams/{team_id}/dar/applications/{id}/questions/{questionId}/reviews/{reviewId}",
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
    public function destroy(DeleteDataAccessApplicationReview $request, int $teamId, int $id, int $questionId, int $reviewId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $this->checkTeamAccess($teamId, $id, 'delete reviews from');

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

        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     * @OA\Delete(
     *      path="/api/v1/teams/{team_id}/dar/applications/{id}/reviews/{reviewId}",
     *      summary="Delete a review from a DAR application",
     *      description="Delete a review from a DAR application",
     *      summary="DataAccessApplicationReview@destroyGlobal",
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
    public function destroyGlobal(DeleteGlobalDataAccessApplicationReview $request, int $teamId, int $id, int $reviewId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $this->checkTeamAccess($teamId, $id, 'delete reviews from');

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

        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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

    private function emailCustodianReview(int $reviewId, int $applicationId): void
    {
        $template = EmailTemplate::where(['identifier' => 'dar.review.custodian'])->first();
        $application = DataAccessApplication::where('id', $applicationId)->first();
        $comments = DataAccessApplicationReview::where('id', $reviewId)
            ->with('comments')
            ->first()
            ->toArray();
        $teamId = array_filter(array_unique(array_column($comments['comments'], 'team_id')))[0];
        $to = $this->getDarManagers($teamId);

        $thread = $this->formatThread($comments);

        foreach ($to as $darManager) {
            $replacements = [
                '[[DAR_MANAGER_FIRST_NAME]]' => $darManager['to']['name'],
                '[[PROJECT_TITLE]]' => $application->project_title,
                '[[THREAD]]' => $thread,
                '[[TEAM_ID]]' => $teamId,
                '[[CURRENT_YEAR]]' => date("Y"),
            ];

            SendEmailJob::dispatch($darManager, $template, $replacements);
        }
    }

    private function emailResearcherReview(int $reviewId, int $applicationId): void
    {
        $template = EmailTemplate::where(['identifier' => 'dar.review.researcher'])->first();
        $application = DataAccessApplication::where('id', $applicationId)->first();
        $comments = DataAccessApplicationReview::where('id', $reviewId)
            ->with('comments')
            ->first()
            ->toArray();
        $teamId = array_filter(array_unique(array_column($comments['comments'], 'team_id')))[0];
        $teamName = Team::where('id', $teamId)->first()->name;
        $user = User::where('id', $application->applicant_id)->first();

        $to = [
            'to' => [
                'email' => $user['email'],
                'name' => $user['name'],
            ],
        ];

        $thread = $this->formatThread($comments);

        $replacements = [
            '[[USER_FIRST_NAME]]' => $user['firstname'],
            '[[PROJECT_TITLE]]' => $application->project_title,
            '[[CUSTODIAN_NAME]]' => $teamName,
            '[[APPLICATION_ID]]' => $applicationId,
            '[[THREAD]]' => $thread,
            '[[CURRENT_YEAR]]' => date("Y"),
        ];

        SendEmailJob::dispatch($to, $template, $replacements);
    }

    private function formatThread(array $comments): string
    {
        $thread = '';
        foreach ($comments['comments'] as $c) {
            if (!is_null($c['team_id'])) {
                $teamName = Team::where('id', $c['team_id'])->select('name')->pluck('name')->first();
                $thread .= $teamName . '<br/>';
                $thread .= $c['comment'] . '<br/><br/>';
            } elseif (!is_null($c['user_id'])) {
                $userName = User::where('id', $c['user_id'])->select('name')->pluck('name')->first();
                $thread .= $userName . '<br/>';
                $thread .= $c['comment'] . '<br/><br/>';
            }
        }
        return $thread;
    }
}
