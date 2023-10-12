<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ActivityLogUserType;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Traits\RequestTransformation;
use App\Exceptions\InternalServerErrorException;
use App\Http\Requests\ActivityLogUserType\GetActivityLogUserType;
use App\Http\Requests\ActivityLogUserType\CreateActivityLogUserType;
use App\Http\Requests\ActivityLogUserType\DeleteActivityLogUserType;
use App\Http\Requests\ActivityLogUserType\EditActivityLogUserType;
use App\Http\Requests\ActivityLogUserType\UpdateActivityLogUserType;

class ActivityLogUserTypeController extends Controller
{
    use RequestTransformation;
    
    /**
     * @OA\Get(
     *      path="/api/v1/activity_log_user_types",
     *      summary="List of system activity log user types",
     *      description="Returns a list of activity log user types enabled on the system",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="name", type="string", example="Name"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $activityLogUserTypes = ActivityLogUserType::paginate(Config::get('constants.per_page'), ['*'], 'page');
        return response()->json(
            $activityLogUserTypes
        );
    }

    /**
     * @OA\Get(
     *      path="/api/v1/activity_log_user_types/{id}",
     *      summary="Return a single system activity log user type",
     *      description="Return a single system activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log user type id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log user type id",
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
     *                  @OA\Property(property="name", type="string", example="Name"),
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
    public function show(GetActivityLogUserType $request, int $id): JsonResponse
    {
        $activityLogUserType = ActivityLogUserType::findOrFail($id);
        if ($activityLogUserType) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $activityLogUserType,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        throw new NotFoundException();
    }

    /**
     * @OA\Post(
     *      path="/api/v1/activity_log_user_types",
     *      summary="Create a new system activity log user type",
     *      description="Creates a new system activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLogUserTypes definition",
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", example="Name"),
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
    public function store(CreateActivityLogUserType $request): JsonResponse
    {
        $activityLogUserType = ActivityLogUserType::create($request->post());
        if ($activityLogUserType) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $activityLogUserType->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        }

        throw new InternalServerErrorException();
    }

    /**
     * @OA\Put(
     *      path="/api/v1/activity_log_user_types/{id}",
     *      summary="Update a system activity log user type",
     *      description="Update a system activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log user type id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log user type id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLogUserTypes definition",
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", example="Name"),
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
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Name"),
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
    public function update(UpdateActivityLogUserType $request, int $id): JsonResponse
    {
        $activityLogUserType = ActivityLogUserType::findOrFail($id);
        $body = $request->post();
        $activityLogUserType->name = $body['name'];

        if ($activityLogUserType->save()) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $activityLogUserType,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } else {
            throw new InternalServerErrorException();
        }

        throw new NotFoundException();
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/activity_log_user_types/{id}",
     *      summary="Edit a system activity log user type",
     *      description="Edit a system activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log user type id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log user type id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLogUserTypes definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Name"),
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
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Name"),
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
     *
     * @param EditActivityLogUserType $request
     * @param integer $id
     * @return JsonResponse
     */
    public function edit(EditActivityLogUserType $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $arrayKeys = [
                'name',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            ActivityLogUserType::where('id', $id)->update($array);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => ActivityLogUserType::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/activity_log_user_types/{id}",
     *      summary="Delete a system activity log user type",
     *      description="Delete a system  activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log user type id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log user type id",
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
    public function destroy(DeleteActivityLogUserType $request, int $id): JsonResponse
    {
        $activityLogUserType = ActivityLogUserType::findOrFail($id);
        if ($activityLogUserType) {
            if ($activityLogUserType->delete()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new InternalServerErrorException();
        }
    }
}
