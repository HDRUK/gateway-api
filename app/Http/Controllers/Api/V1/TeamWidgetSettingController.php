<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;

use App\Models\WidgetSetting;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\LoggingContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeamWidgetSettings\DeleteWidgetSettingsByTeamId;
use App\Http\Requests\TeamWidgetSettings\GetWidgetSettingsByTeamId;
use App\Http\Requests\TeamWidgetSettings\CreateWidgetSettingsByTeamId;

class TeamWidgetSettingController extends Controller
{
    use LoggingContext;

    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/widget_settings",
     *    operationId="fetch_by_teamid_widgets_settings",
     *    tags={"WidgetSettings"},
     *    summary="TeamWidgetSettingController@index",
     *    description="Get Widget Settings by Team Id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Team Id"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          )
     *       )
     *    )
     * )
     */
    public function index(GetWidgetSettingsByTeamId $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widgetSettings = WidgetSetting::where('team_id', $teamId)
                ->with(['team:id,name'])
                ->get();

            return response()->json(['data' => $widgetSettings]);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            \Log::info($e->getMessage(), $loggingContext);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/teams/{teamId}/widget_settings",
     *    deprecated=true,
     *    operationId="create_widget_settings",
     *    tags={"WidgetSettings"},
     *    summary="TeamWidgetSettingController@store",
     *    description="Create a new widget settings",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Team Id"
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="colours", type="array", @OA\Items()),
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=201,
     *       description="Created",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="integer", example="100")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
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
     */
    public function store(CreateWidgetSettingsByTeamId $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            WidgetSetting::where('team_id', $teamId)->delete();

            $widgetSettings = WidgetSetting::create([
                'team_id' => $teamId,
                'colours' => $input['colours'],
            ]);

            return response()->json([
                 'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                 'data' => $widgetSettings->id,
             ], Config::get('statuscodes.STATUS_CREATED.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/teams/{teamId}/widget_settings/{id}",
     *    deprecated=true,
     *    operationId="delete_widget_settings",
     *    tags={"WidgetSettings"},
     *    summary="TeamWidgetSettingController@destroy",
     *    description="Delete widget settings",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Team Id"
     *    ),
     *    @OA\Parameter(
     *       name="id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Widget Settings id"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
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
     */
    public function destroy(DeleteWidgetSettingsByTeamId $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widgetSettings = WidgetSetting::query()
                ->where([
                    'id' => $id,
                    'team_id' => $teamId,
                ])
                ->first();

            if (is_null($widgetSettings)) {
                return response()->json([
                    'message' => 'not found',
                ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }

            $widgetSettings->delete();

            return response()->json([
                'message' => 'success',
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }
}
