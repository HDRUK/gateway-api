<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\Widget;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\Tool;
use App\Models\Dur;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\LoggingContext;

class WidgetController extends Controller
{
    use LoggingContext;


    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/widgets",
     *    operationId="fetch_all_widgets",
     *    tags={"Widgets"},
     *    summary="WidgetController@get",
     *    description="Get All Widgets",
     *    security={{"bearerAuth":{}}},
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
    public function get(Request $request, int $teamId): JsonResponse
    {
        //\Log::info('This is a log message.'. $teamId);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widgets = Widget::where('team_id', $teamId)
                ->get([
                    'id',
                    'widget_name',
                    'size_width',
                    'size_height',
                    'updated_at',
                    'unit'
                ]);

            return response()->json([ 'data' => $widgets]);

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
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/widgets/{id}",
     *    operationId="fetch_widget",
     *    tags={"Widgets"},
     *    summary="WidgetController@retrieve",
     *    description="Get a single Widget",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response=200,
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="object",
     *             example={"id": 1, "widget_name": "Example Widget"}
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Widget not found",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found")
     *       )
     *    )
     * )
     */

    public function retrieve(Request $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widget = Widget::where('id', $id)
               ->where('team_id', $teamId)
               ->first();

            if (! $widget) {
                return response()->json(['message' => 'not found'], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }


            return response()->json([ 'data' => $widget]);

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
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/widgets/data",
     *      tags={"Widgets"},
     *      summary="WidgetController@getWidgetData",
     *      description="Fetch lightweight data (id, name, etc.) for multiple teams across datasets, tools, collections, and DURS",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="teamIds",
     *          in="query",
     *          required=true,
     *          description="Comma-separated list of team IDs to filter data",
     *          example="1,2,3",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Aggregated data retrieved successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="datasets", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="tools", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="collections", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="durs", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid or missing teamIds parameter",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="teamIds parameter is required")
     *          )
     *      )
     * )
     */
    public function getWidgetData(Request $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $teamIdsParam = $request->query('teamIds');
            if (!$teamIdsParam) {
                return response()->json([
                    'message' => 'teamIds parameter is required',
                ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
            }


            $teamIds = is_array($teamIdsParam)
                ? $teamIdsParam
                : explode(',', $teamIdsParam);

            $teamIds = array_map('intval', $teamIds);

            $datasets = Dataset::whereIn('team_id', $teamIds)
                ->get(['datasetid', 'team_id', 'id'])
                ->map(fn ($dataset) => [
                    'datasetid' => $dataset->datasetid,
                    'title' => $dataset->getTitle(),
                    'team_id' => $dataset->team_id,
                ]);


            $tools = Tool::whereIn('team_id', $teamIds)
                ->get(['id', 'name', 'team_id']);

            $collections = Collection::whereIn('team_id', $teamIds)
                ->get(['id', 'name', 'team_id']);

            $durs = Dur::whereIn('team_id', $teamIds)
                ->get(['id', 'project_title', 'team_id']);

            return response()->json([ 'data' => [
                'datasets' => $datasets,
                'tools' => $tools,
                'collections' => $collections,
                'durs' => $durs,
            ]], Config::get('statuscodes.STATUS_OK.code'));
        } catch (\Exception $e) {
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
     * @OA\Post(
     *      path="/api/v1/teams/{teamId}/widgets",
     *      operationId="create_widget",
     *      summary="Create a new widget",
     *      description="Creates a new widget for a given team",
     *      tags={"Widgets"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="teamId",
     *          in="path",
     *          description="Team ID the widget belongs to",
     *          required=true,
     *          example="5",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"widget_name"},
     *              @OA\Property(property="widget_name", type="string", example="Cohort Dashboard"),
     *              @OA\Property(property="size_width", type="integer", example=400),
     *              @OA\Property(property="size_height", type="integer", example=300),
     *              @OA\Property(property="unit", type="string", example="px"),
     *              @OA\Property(property="include_search_bar", type="boolean", example=true),
     *              @OA\Property(property="include_cohort_link", type="boolean", example=false),
     *              @OA\Property(property="keep_proportions", type="boolean", example=true),
     *              @OA\Property(property="permitted_domains", type="string", example="example.com,example.org")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Widget created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Validation failed",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="validation error")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function store(Request $request, int $teamId)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $validated = $request->validate([
                'widget_name'          => 'required|string|max:255',
                'size_width'           => 'nullable|integer',
                'size_height'          => 'nullable|integer',
                'unit'                 => 'nullable|string|in:px,%,rem',
                'include_search_bar'   => 'boolean',
                'include_cohort_link'  => 'boolean',
                'keep_proportions'     => 'boolean',
                'permitted_domains'    => 'nullable|string',
                'data_custodian_entities_ids' => 'nullable|string',
                'included_datasets'    => 'nullable|string',
                'included_data_uses'   => 'nullable|string',
                'included_scripts'     => 'nullable|string',
                'included_collections' => 'nullable|string',
            ]);

            $validated['team_id'] = $teamId;

            $widget = Widget::create($validated);

            return response()->json([
                 'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                 'data' => $widget->id,
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
 * @OA\Patch(
 *      path="/api/v1/teams/{teamId}/widgets/{id}",
 *      operationId="update_widget",
 *      summary="Update a widget",
 *      description="Update specific fields of a widget belonging to a given team",
 *      tags={"Widgets"},
 *      security={{"bearerAuth":{}}},
 *      @OA\Parameter(
 *          name="teamId",
 *          in="path",
 *          description="Team ID",
 *          required=true,
 *          example="5",
 *          @OA\Schema(type="integer")
 *      ),
 *      @OA\Parameter(
 *          name="id",
 *          in="path",
 *          description="Widget ID",
 *          required=true,
 *          example="12",
 *          @OA\Schema(type="integer")
 *      ),
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(
 *              @OA\Property(property="widget_name", type="string", example="Updated Widget"),
 *              @OA\Property(property="size_width", type="integer", example=500),
 *              @OA\Property(property="size_height", type="integer", example=300),
 *              @OA\Property(property="unit", type="string", example="px"),
 *              @OA\Property(property="include_search_bar", type="boolean", example=true),
 *              @OA\Property(property="include_cohort_link", type="boolean", example=false)
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Widget updated successfully",
 *          @OA\JsonContent(
 *              @OA\Property(property="message", type="string", example="success"),
 *              @OA\Property(property="data", type="object")
 *          )
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Widget not found",
 *          @OA\JsonContent(
 *              @OA\Property(property="message", type="string", example="not found")
 *          )
 *      )
 * )
 */
    public function update(Request $request, int $teamId, int $id)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widget = Widget::where('id', $id)
                ->where('team_id', $teamId)
                ->first();

            if (! $widget) {
                return response()->json(['message' => 'not found'], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }

            $validated = $request->validate([
                'widget_name'          => 'sometimes|string|max:255',
                'size_width'           => 'sometimes|integer',
                'size_height'          => 'sometimes|integer',
                'unit'                 => 'sometimes|string|in:px,%,rem',
                'include_search_bar'   => 'sometimes|boolean',
                'include_cohort_link'  => 'sometimes|boolean',
                'keep_proportions'     => 'sometimes|boolean',
                'permitted_domains'    => 'sometimes|string|nullable',
            ]);

            $widget->update($validated);

            return response()->json([
                'message' => 'success',
                'data' => $widget
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


    /**
     * @OA\Delete(
     *      path="/api/v1/teams/{teamId}/widgets/{id}",
     *      operationId="delete_widget",
     *      summary="Delete a widget",
     *      description="Soft delete a widget belonging to a specific team",
     *      tags={"Widgets"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="teamId",
     *         in="path",
     *         description="Team ID",
     *         required=true,
     *         example="5",
     *         @OA\Schema(type="integer"),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Widget ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(type="integer"),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Widget not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Widget deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function destroy(Request $request, int $teamId, int $id)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widget = Widget::where('id', $id)
                ->where('team_id', $teamId)
                ->first();

            if (! $widget) {
                return response()->json([
                    'message' => 'not found',
                ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }

            $widget->delete();

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
