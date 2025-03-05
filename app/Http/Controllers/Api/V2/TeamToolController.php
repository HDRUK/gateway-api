<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;

use Carbon\Carbon;
use App\Models\Tool;
use App\Models\DurHasTool;
use App\Models\ToolHasTag;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\IndexElastic;
use App\Models\CollectionHasTool;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\ToolsV2Helper;
use App\Models\PublicationHasTool;
use App\Models\ToolHasTypeCategory;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Models\DatasetVersionHasTool;
use App\Models\ToolHasProgrammingPackage;
use App\Http\Traits\RequestTransformation;
use App\Models\ToolHasProgrammingLanguage;
use App\Http\Requests\V2\Tool\CreateToolByTeamId;
use App\Http\Requests\V2\Tool\GetToolByTeamAndId;
use App\Http\Requests\V2\Tool\EditToolByTeamIdById;
use App\Http\Requests\V2\Tool\DeleteToolByTeamIdById;
use App\Http\Requests\V2\Tool\GetToolByTeamAndStatus;
use App\Http\Requests\V2\Tool\UpdateToolByTeamIdById;
use App\Http\Requests\V2\Tool\GetToolByTeamByIdByStatus;

class TeamToolController extends Controller
{
    use IndexElastic;
    use RequestTransformation;
    use CheckAccess;
    use ToolsV2Helper;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/v2/teams/{teamId}/tools/{status}",
     *     operationId="fetch_all_tool_by_team_and_status_v2",
     *     tags={"Tool"},
     *     summary="TeamToolController@indexStatus",
     *     description="Returns a list of a teams tools",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="teamId",
     *         in="path",
     *         description="ID of the team",
     *         required=true,
     *         @OA\Schema( type="integer", format="int64" )
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="path",
     *         description="Status of the tool (active, draft, or archived). Defaults to active if not provided.",
     *         required=false,
     *         @OA\Schema( type="string", enum={"active", "draft", "archived"}, default="active" )
     *     ),
     *     @OA\Response(
     *        response="200",
     *        description="Success response",
     *        @OA\JsonContent(
     *           @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 example="[]",
     *                 @OA\Items( type="array", @OA\Items()
     *              ),
     *           ),
     *        ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     *
     * @param  GetToolByTeamAndStatus  $request
     * @param  int  $teamId
     * @param  string|null  $status
     * @return JsonResponse
     */
    public function indexStatus(GetToolByTeamAndStatus $request, int $teamId, ?string $status = 'active'): JsonResponse
    {
        try {
            $perPage = request('per_page', Config::get('constants.per_page'));

            // Perform query for the matching tools with filters, sorting, and pagination
            $tools = Tool::where([
                'team_id' => $teamId,
                'status' => strtoupper($status),
            ])
            ->with([
                'user',
                'tag',
                'team',
                'license',
                'publications',
                'durs',
                'collections',
                'category',
                'typeCategory',
            ])
            ->where('enabled', 1)
            ->applySorting()
            ->paginate($perPage, ['*'], 'page');

            // Transform collection to include datasets
            $tools->map(function ($tool) {
                $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
                return $tool;
            });

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool get all',
            ]);

            return response()->json(
                $tools
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v2/teams/{teamId}/tools/{id}",
     *    operationId="fetch_tools_by_team_and_by_id_v2",
     *    tags={"Tool"},
     *    summary="TeamToolController@show",
     *    description="Get tool by team id and by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tool id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="tool id",
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
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     *
     * @param  GetToolByTeamAndId  $request
     * @param  int  $teamId
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(GetToolByTeamAndId $request, int $teamId, int $id): JsonResponse
    {
        try {
            $tool = $this->getToolByTeamIdAndById($teamId, $id, true);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $tool,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v2/teams/{teamId}/tools/{id}/status/{status}",
     *    operationId="fetch_tools_by_team_and_by_id_by_status_v2",
     *    tags={"Tool"},
     *    summary="TeamToolController@showStatus",
     *    description="Get tool by team id and by id by status",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer",  description="team id" ),
     *    ),
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tool id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="tool id" ),
     *    ),
     *    @OA\Parameter(
     *       name="status",
     *       in="path",
     *       description="tool status",
     *       required=true,
     *       example="active",
     *       @OA\Schema( type="string", description="tool status" ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property( property="message", type="string", example="success" ),
     *          @OA\Property( property="data", type="array", example="[]", @OA\Items( type="array", @OA\Items() ) ),
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
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       ),
     *    ),
     * )
     *
     * @param  GetToolByTeamByIdByStatus  $request
     * @param  int  $teamId
     * @param  int  $id
     * @param  string  $status
     * @return JsonResponse
     */
    public function showStatus(GetToolByTeamByIdByStatus $request, int $teamId, int $id, string $status): JsonResponse
    {
        try {
            $tool = $this->getToolByTeamIdAndByIdByStatus($teamId, $id, $status);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $tool,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v2/teams/{teamId}/tools",
     *    operationId="create_tools_by_team_v2",
     *    tags={"Tools"},
     *    summary="ToolController@store",
     *    description="Create a new tool by team v2",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer",  description="team id" ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="name", type="string", example="Similique sapiente est vero eum." ),
     *             @OA\Property( property="url", type="string", example="http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim" ),
     *             @OA\Property( property="description", type="string", example="Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel." ),
     *             @OA\Property( property="results_insights", type="string", example="Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel." ),
     *             @OA\Property( property="license", type="integer", example="1" ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="dataset", type="array", @OA\Items(
     *                type="object",
     *                @OA\Property( property="id", type="integer", example=1 ),
     *                @OA\Property( property="link_type", type="string", example="Other" ),
     *             )),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="associated_authors", type="string", example="string" ),
     *             @OA\Property( property="contact_address", type="string", example="string" ),
     *             @OA\Property( property="publications", type="array", @OA\Items() ),
     *             @OA\Property( property="durs", type="array", @OA\Items() ),
     *             @OA\Property( property="collections", type="array", @OA\Items() ),
     *             @OA\Property( property="any_dataset", type="boolean", example=false ),
     *             @OA\Property( property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"} ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=201,
     *       description="Created",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="integer", example="100")
     *       ),
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="bad request",
     *      @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="bad request"),
     *      ),
     *   ),
     *   @OA\Response(
     *      response=401,
     *      description="Unauthorized",
     *      @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="unauthorized"),
     *      ),
     *   ),
     *   @OA\Response(
     *      response=500,
     *      description="Error",
     *      @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="error"),
     *      ),
     *   ),
     * )
     *
     * @param  CreateToolByTeamId  $request
     * @param  int  $teamId
     * @return JsonResponse
     */
    public function store(CreateToolByTeamId $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        if (!is_null($teamId)) {
            $this->checkAccess($input, $teamId, null, 'team');
        }

        try {
            $arrayKeys = [
                'mongo_object_id',
                'name',
                'url',
                'description',
                'results_insights',
                'license',
                'tech_stack',
                'category_id',
                'user_id',
                'enabled',
                'mongo_id',
                'associated_authors',
                'contact_address',
                'any_dataset',
                'status',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            $array['name'] = formatCleanInput($input['name']);
            $array['team_id'] = $teamId;
            $tool = Tool::create($array);
            $toolId = $tool->id;

            $this->insertToolHasTag($input['tag'], (int)$toolId);
            if (array_key_exists('dataset', $input)) {
                $this->insertDatasetVersionHasTool($input['dataset'], (int)$toolId);
            }
            if (array_key_exists('programming_language', $input)) {
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int)$toolId);
            }
            if (array_key_exists('programming_package', $input)) {
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int)$toolId);
            }
            if (array_key_exists('type_category', $input)) {
                $this->insertToolHasTypeCategory($input['type_category'], (int)$toolId);
            }

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($toolId, $publications, (int)$jwtUser['id']);

            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$toolId);
            }

            $collections = array_key_exists('collections', $input) ? $input['collections'] : [];
            $this->checkCollections($toolId, $collections, (int)$jwtUser['id']);

            $currentTool = Tool::where('id', $toolId)->first();
            if($currentTool->status === Tool::STATUS_ACTIVE) {
                $this->indexElasticTools((int) $toolId);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool ' . $tool->id . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $toolId,
            ], 201);
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
     *    path="/api/v2/teams/{teamId}/tools/{id}",
     *    operationId="update_tools_by_teamid_v2",
     *    tags={"Tools"},
     *    summary="TeamToolController@update",
     *    description="Update tools by team id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tool id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="tool id" ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="name", type="string", example="Similique sapiente est vero eum." ),
     *             @OA\Property( property="url", type="string", example="http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim" ),
     *             @OA\Property( property="description", type="string", example="Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel." ),
     *             @OA\Property( property="license", type="integer", example="1" ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="dataset", type="array", @OA\Items(
     *                type="object",
     *                @OA\Property( property="id", type="integer", example=1 ),
     *                @OA\Property( property="link_type", type="string", example="Other" ),
     *             )),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="associated_authors", type="string", example="string" ),
     *             @OA\Property( property="contact_address", type="string", example="string" ),
     *             @OA\Property( property="publications", type="array", @OA\Items() ),
     *             @OA\Property( property="durs", type="array", @OA\Items() ),
     *             @OA\Property( property="collections", type="array", @OA\Items() ),
     *             @OA\Property( property="any_dataset", type="boolean", example=false ),
     *             @OA\Property( property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"} ),
     *          ),
     *       ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="integer", example="100")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="bad request"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     *
     * @param  UpdateToolByTeamIdById  $request
     * @param  int  $teamId
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(UpdateToolByTeamIdById $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initTool = Tool::where([
            'id' => $id,
            'team_id' => $teamId,
        ])->first();
        $this->checkAccess($input, $initTool->team_id, null, 'user');

        try {

            if ($initTool['status'] === Tool::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current tool! Status already "ARCHIVED"');
            }

            $arrayKeys = [
                'mongo_object_id',
                'name',
                'url',
                'description',
                'license',
                'tech_stack',
                'category_id',
                'user_id',
                'enabled',
                'mongo_id',
                'associated_authors',
                'contact_address',
                'any_dataset',
                'status',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            if (array_key_exists('name', $input)) {
                $array['name'] = formatCleanInput($input['name']);
            }
            $array['team_id'] = $teamId;
            Tool::where([
                'id' => $id,
                'team_id' => $teamId,
            ])->first()->update($array);

            ToolHasTag::where('tool_id', $id)->forceDelete();
            $this->insertToolHasTag($input['tag'], (int)$id);

            if (array_key_exists('dataset', $input)) {
                DatasetVersionHasTool::where('tool_id', $id)->forceDelete();
                $this->insertDatasetVersionHasTool($input['dataset'], (int)$id);
            }

            if (array_key_exists('programming_language', $input)) {
                ToolHasProgrammingLanguage::where('tool_id', $id)->forceDelete();
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int)$id);
            }

            if (array_key_exists('programming_package', $input)) {
                ToolHasProgrammingPackage::where('tool_id', $id)->forceDelete();
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int)$id);
            }

            if (array_key_exists('type_category', $input)) {
                ToolHasTypeCategory::where('tool_id', $id)->forceDelete();
                $this->insertToolHasTypeCategory($input['type_category'], (int)$id);
            }

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($id, $publications, (int)$jwtUser['id']);

            DurHasTool::where('tool_id', $id)->forceDelete();
            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$id);
            }

            $collections = array_key_exists('collections', $input) ? $input['collections'] : [];
            $this->checkCollections($id, $collections, (int)$jwtUser['id']);

            $currentTool = Tool::where('id', $id)->first();
            if ($currentTool->status === Tool::STATUS_ACTIVE) {
                if ($request['enabled']) { //note Calum - this is crazy inconsistent
                    $this->indexElasticTools((int) $id);
                } else {
                    //note Calum - adding this to be safe
                    $this->deleteToolFromElastic((int) $id);
                }
            } else {
                $this->deleteToolFromElastic((int) $id);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getToolById($id),
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
     * @OA\Patch(
     *    path="/api/v2/teams/{teamId}/tools/{id}",
     *    operationId="edit_tools_by_teamid_v2",
     *    tags={"Tools"},
     *    summary="TeamToolController@edit",
     *    description="Edit tool by id and by teamid",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="team id" ),
     *    ),
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tool id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="tool id" ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="name", type="string", example="Similique sapiente est vero eum." ),
     *             @OA\Property( property="url", type="string", example="http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim" ),
     *             @OA\Property( property="description", type="string", example="Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel." ),
     *             @OA\Property( property="license", type="integer", example="1" ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="dataset", type="array", @OA\Items(
     *                type="object",
     *                @OA\Property( property="id", type="integer", example=1 ),
     *                @OA\Property( property="link_type", type="string", example="Other" ),
     *             )),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="associated_authors", type="string", example="string" ),
     *             @OA\Property( property="contact_address", type="string", example="string" ),
     *             @OA\Property( property="publications", type="array", @OA\Items() ),
     *             @OA\Property( property="durs", type="array", @OA\Items() ),
     *             @OA\Property( property="collections", type="array", @OA\Items() ),
     *             @OA\Property( property="any_dataset", type="boolean", example=false ),
     *             @OA\Property( property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"} ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example="100")
     *          ),
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="bad request",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="bad request"),
     *       ),
     *    ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     *
     * @param  EditToolByTeamIdById  $request
     * @param  int  $teamId
     * @param  int  $id
     * @return JsonResponse
     */
    public function edit(EditToolByTeamIdById $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $toolModel = Tool::where([
            'id' => $id,
            'team_id' => $teamId,
        ])->first();
        $this->checkAccess($input, null, $toolModel->user_id, 'user');

        try {
            $arrayKeys = [
                'mongo_object_id',
                'name',
                'url',
                'description',
                'license',
                'tech_stack',
                'category_id',
                'user_id',
                'enabled',
                'mongo_id',
                'associated_authors',
                'contact_address',
                'any_dataset',
                'status',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            if (array_key_exists('name', $input)) {
                $array['name'] = formatCleanInput($input['name']);
            }
            $array['team_id'] = $teamId;
            $initTool = Tool::where('id', $id)->first();

            if ($initTool['status'] === Tool::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current tool! Status already "ARCHIVED"');
            }

            Tool::where([
                'id' => $id,
                'team_id' => $teamId,
            ])->update($array);

            if (array_key_exists('tag', $input)) {
                ToolHasTag::where('tool_id', $id)->forceDelete();
                $this->insertToolHasTag($input['tag'], (int)$id);
            };

            if (array_key_exists('dataset', $input)) {
                DatasetVersionHasTool::where('tool_id', $id)->forceDelete();
                $this->insertDatasetVersionHasTool($input['dataset'], (int)$id);
            }

            if (array_key_exists('programming_language', $input)) {
                ToolHasProgrammingLanguage::where('tool_id', $id)->forceDelete();
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int)$id);
            }
            if (array_key_exists('programming_package', $input)) {
                ToolHasProgrammingPackage::where('tool_id', $id)->forceDelete();
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int)$id);
            }
            if (array_key_exists('type_category', $input)) {
                ToolHasTypeCategory::where('tool_id', $id)->forceDelete();
                $this->insertToolHasTypeCategory($input['type_category'], (int)$id);
            }

            if (array_key_exists('publications', $input)) {
                $publications = $input['publications'];
                $this->checkPublications($id, $publications, (int)$jwtUser['id']);
            }

            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$id);
            }

            if (array_key_exists('collections', $input)) {
                $collections = $input['collections'];
                $this->checkCollections($id, $collections, (int)$jwtUser['id']);
            }

            $currentTool = Tool::where('id', $id)->first();
            if($currentTool->status === Tool::STATUS_ACTIVE) {
                $this->indexElasticTools($id);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getToolById($id),
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
     * @OA\Delete(
     *    path="/api/v2/teams/{teamId}/tools/{id}",
     *    operationId="delete_tools_by_teamid_v2",
     *    tags={"Tools"},
     *    summary="TeamToolController@destroy",
     *    description="Delete tool by id and by team_id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="team id" ),
     *    ),
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tool id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="tool id" ),
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
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     *
     * @param  DeleteToolByTeamIdById  $request
     * @param  int  $teamId
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(DeleteToolByTeamIdById $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $tool = Tool::where([
            'id' => $id,
            'team_id' => $teamId,
        ])->first();
        $this->checkAccess($input, $teamId, null, 'user');

        try {
            if ($tool) {
                $tool->deleted_at = Carbon::now();
                $tool->status = Tool::STATUS_ARCHIVED;
                $tool->save();
                ToolHasTag::where('tool_id', $id)->delete();
                DatasetVersionHasTool::where('tool_id', $id)->delete();
                ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
                ToolHasProgrammingPackage::where('tool_id', $id)->delete();
                ToolHasTypeCategory::where('tool_id', $id)->delete();
                PublicationHasTool::where('tool_id', $id)->delete();
                DurHasTool::where('tool_id', $id)->delete();
                CollectionHasTool::where('tool_id', $id)->delete();

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'DELETE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Tool ' . $id . ' deleted',
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
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
