<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
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
use App\Exceptions\UnauthorizedException;
use App\Models\ToolHasProgrammingPackage;
use App\Http\Traits\RequestTransformation;
use App\Models\ToolHasProgrammingLanguage;
use App\Http\Requests\V2\Tool\CreateToolByUserId;
use App\Http\Requests\V2\Tool\GetToolByUserAndId;
use App\Http\Requests\V2\Tool\EditToolByUserIdById;
use App\Http\Requests\V2\Tool\DeleteToolByUserIdById;
use App\Http\Requests\V2\Tool\GetToolByUserAndStatus;
use App\Http\Requests\V2\Tool\UpdateToolByUserIdById;
use App\Http\Requests\V2\Tool\GetToolCountByUserAndStatus;

class UserToolController extends Controller
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
     *     path="/api/v2/users/{userId}/tools/{status}",
     *     operationId="fetch_all_tool_by_user_and_status_v2",
     *     tags={"Tool"},
     *     summary="UserToolController@indexStatus",
     *     description="Returns a list of a user tools",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID of the user",
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
     *           @OA\Property( property="data", type="array", example="[]", @OA\Items( type="array", @OA\Items() ) ),
     *        ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     *
     * @param  GetToolByUserAndStatus  $request
     * @param  int  $userId
     * @param  string|null  $status
     * @return JsonResponse
     */
    public function indexStatus(GetToolByUserAndStatus $request, int $userId, ?string $status = 'active'): JsonResponse
    {
        $input = $request->all();

        $this->checkAccess($input, null, $userId, 'user');

        try {
            $perPage = request('per_page', Config::get('constants.per_page'));
            $filterTitle = request('title', null);

            // Perform query for the matching tools with filters, sorting, and pagination
            $tools = Tool::where([
                'user_id' => $userId,
                'status' => strtoupper($status),
            ])
            ->when($filterTitle, function ($query) use ($filterTitle) {
                return $query->where('name', 'like', '%' . $filterTitle . '%');
            })
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
                'description' => 'User Tool get all by status',
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
     *    path="/api/v2/users/{userId}/tools/count/{field}",
     *    operationId="count_user_unique_fields_tools_v2",
     *    tags={"Tools"},
     *    summary="UserToolController@count",
     *    description="Get user counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="user id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="field",
     *       in="path",
     *       description="name of the field to perform a count on",
     *       required=true,
     *       example="status",
     *       @OA\Schema(
     *          type="string",
     *          description="status field",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="object",
     *          )
     *       )
     *    )
     * )
     */
    public function count(GetToolCountByUserAndStatus $request, int $userId, string $field): JsonResponse
    {
        $this->checkAccess($request->all(), null, $userId, 'user');

        try {
            $counts = Tool::where('user_id', $userId)->applyCount();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'User Tool count',
            ]);

            return response()->json([
                "data" => $counts
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v2/users/{userId}/tools/{id}",
     *    operationId="fetch_tools_by_user_and_by_id_v2",
     *    tags={"Tool"},
     *    summary="UserToolController@show",
     *    description="Get tool by user id and by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="user id" ),
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
     *       name="view_type",
     *       in="query",
     *       description="Query flag to show full tool data or a trimmed version (defaults to full).",
     *       required=false,
     *       @OA\Schema(
     *          type="string",
     *          default="full",
     *          description="Flag to show all data ('full') or trimmed data ('mini')"
     *       ),
     *       example="full"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property( property="data", type="array", example="[]", @OA\Items( type="array", @OA\Items() ) ),
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
     * @param  GetToolByUserAndId  $request
     * @param  int  $userId
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(GetToolByUserAndId $request, int $userId, int $id): JsonResponse
    {
        $this->checkAccess($request->all(), null, $userId, 'user');

        $viewType = $request->query('view_type', 'full');
        $trimmed = $viewType === 'mini';

        try {
            $tool = $this->getToolById($id, userId: $userId, onlyActiveRelated: true, trimmed: $trimmed);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User Tool get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $tool,
            ], 200);
        } catch (NotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
     *    path="/api/v2/users/{userId}/tools",
     *    operationId="create_tools_by_user_v2",
     *    tags={"Tools"},
     *    summary="UserToolController@store",
     *    description="Create a new tool by user v2",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer",  description="user id" ),
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
     * @param  CreateToolByUserId  $request
     * @param  int  $userId
     * @return JsonResponse
     */
    public function store(CreateToolByUserId $request, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $teamId = array_key_exists('team_id', $input) ? $input['team_id'] : null;
        if (!is_null($teamId)) {
            $this->checkAccess($input, null, $userId, 'team');
        }

        try {
            $arrayKeys = [
                'name',
                'url',
                'description',
                'results_insights',
                'license',
                'tech_stack',
                'category_id',
                'team_id',
                'enabled',
                'mongo_id',
                'associated_authors',
                'contact_address',
                'any_dataset',
                'status',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            $array['name'] = formatCleanInput($input['name']);
            $array['user_id'] = $userId;

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
            $this->checkPublications($toolId, $publications, $userId);

            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$toolId);
            }

            $collections = array_key_exists('collections', $input) ? $input['collections'] : [];
            $this->checkCollections($toolId, $collections, $userId);

            $currentTool = Tool::where('id', $toolId)->first();
            if ($currentTool->status === Tool::STATUS_ACTIVE) {
                $this->indexElasticTools((int) $toolId);
            }

            Auditor::log([
                'user_id' => $userId,
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User Tool ' . $tool->id . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $toolId,
            ], 201);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v2/users/{userId}/tools/{id}",
     *    operationId="update_tools_by_user_v2",
     *    tags={"Tools"},
     *    summary="UserToolController@update",
     *    description="Update tools by user id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="user id", ),
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
     * @param  UpdateToolByUserIdById  $request
     * @param  int  $userId
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(UpdateToolByUserIdById $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initTool = Tool::where('id', $id)->first();
        if (!$initTool) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, null, $userId, 'user');
        if ($initTool->user_id !== $userId) {
            throw new UnauthorizedException();
        }

        try {
            $arrayKeys = [
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

            $array = $this->checkUpdateArray($input, $arrayKeys);

            if (array_key_exists('name', $input)) {
                $array['name'] = formatCleanInput($input['name']);
            }

            // if not supplied, set fields which have a NOT NULL constraint to their defaults
            if (is_null($array['any_dataset'])) {
                $array['any_dataset'] = 0;
            }
            if (is_null($array['status'])) {
                $array['status'] = Tool::STATUS_DRAFT;
            }

            Tool::where([
                'id' => $id,
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
            $this->checkPublications($id, $publications, $userId);

            DurHasTool::where('tool_id', $id)->forceDelete();
            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$id);
            }

            $collections = array_key_exists('collections', $input) ? $input['collections'] : [];
            $this->checkCollections($id, $collections, $userId);

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
                'user_id' => $userId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User Tool ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getToolById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v2/users/{userId}/tools/{id}",
     *    operationId="edit_tools_by_user_v2",
     *    tags={"Tools"},
     *    summary="UserToolController@edit",
     *    description="Edit tool by id and by user",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="user id" ),
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
     * @param  EditToolByUserIdById  $request
     * @param  int  $userId
     * @param  int  $id
     * @return JsonResponse
     */
    public function edit(EditToolByUserIdById $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initTool = Tool::where('id', $id)->first();
        if (!$initTool) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, null, $userId, 'user');
        if ($initTool->user_id !== $userId) {
            throw new UnauthorizedException();
        }

        try {
            $arrayKeys = [
                'name',
                'url',
                'description',
                'results_insights',
                'license',
                'tech_stack',
                'category_id',
                'team_id',
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
            $array['user_id'] = $userId;
            $initTool = Tool::where('id', $id)->first();

            Tool::where([
                'id' => $id,
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
                $this->checkPublications($id, $publications, $userId);
            }

            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$id);
            }

            if (array_key_exists('collections', $input)) {
                $collections = $input['collections'];
                $this->checkCollections($id, $collections, $userId);
            }

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
                'user_id' => $userId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User Tool ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getToolById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v2/users/{userId}/tools/{id}",
     *    operationId="delete_tools_by_user_v2",
     *    tags={"Tools"},
     *    summary="UserToolController@destroy",
     *    description="Delete tool by id and by user",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="user id" ),
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
     * @param  DeleteToolByUserIdById  $request
     * @param  int  $userId
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(DeleteToolByUserIdById $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $tool = Tool::where('id', $id)->first();
        if (!$tool) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, null, $userId, 'user');
        if ($tool->user_id !== $userId) {
            throw new UnauthorizedException();
        }

        try {
            ToolHasTag::where('tool_id', $id)->delete();
            DatasetVersionHasTool::where('tool_id', $id)->delete();
            ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
            ToolHasProgrammingPackage::where('tool_id', $id)->delete();
            ToolHasTypeCategory::where('tool_id', $id)->delete();
            PublicationHasTool::where('tool_id', $id)->delete();
            DurHasTool::where('tool_id', $id)->delete();
            CollectionHasTool::where('tool_id', $id)->delete();
            Tool::where('id', $id)->delete();

            Auditor::log([
                'user_id' => $userId,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Tool ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
