<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Tag;
use App\Models\Dur;
use App\Models\Collection as CollectionModel;
use App\Models\Tool;
use App\Models\Publication;
use App\Models\Dataset;
use App\Models\License;
use App\Models\DurHasTool;
use App\Models\ToolHasTag;
use App\Http\Enums\TagType;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use Illuminate\Support\Carbon;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\IndexElastic;
use App\Models\CollectionHasTool;
use Illuminate\Http\JsonResponse;
use App\Models\PublicationHasTool;
use Illuminate\Support\Collection;
use App\Http\Requests\Tool\GetTool;
use App\Models\ToolHasTypeCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tool\EditTool;
use App\Exceptions\NotFoundException;
use App\Models\DatasetVersionHasTool;
use App\Http\Requests\Tool\CreateTool;
use App\Http\Requests\Tool\DeleteTool;
use App\Http\Requests\Tool\UpdateTool;
use App\Models\ToolHasProgrammingPackage;
use App\Http\Traits\RequestTransformation;
use App\Models\ToolHasProgrammingLanguage;

class ToolController extends Controller
{
    use IndexElastic;
    use RequestTransformation;
    use CheckAccess;

    /**
     * constructor method
     */
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/tools",
     *    operationId="fetch_all_tools",
     *    tags={"Tools"},
     *    summary="Fetch all tools",
     *    description="Get all tools with optional filters and sorting",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="mongo_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter tools by mongo ID"
     *    ),
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter tools by team ID"
     *    ),
     *    @OA\Parameter(
     *       name="user_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter tools by user ID"
     *    ),
     *    @OA\Parameter(
     *       name="title",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter tools by title"
     *    ),
     *    @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string", example="name:asc"),
     *       description="Sort tools by a specific field and direction, e.g., 'name:asc' or 'created_at:desc'"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(type="object")
     *          ),
     *          @OA\Property(property="current_page", type="integer"),
     *          @OA\Property(property="first_page_url", type="string"),
     *          @OA\Property(property="from", type="integer"),
     *          @OA\Property(property="last_page", type="integer"),
     *          @OA\Property(property="last_page_url", type="string"),
     *          @OA\Property(property="links", type="array", @OA\Items(type="object")),
     *          @OA\Property(property="next_page_url", type="string"),
     *          @OA\Property(property="path", type="string"),
     *          @OA\Property(property="per_page", type="integer"),
     *          @OA\Property(property="prev_page_url", type="string"),
     *          @OA\Property(property="to", type="integer"),
     *          @OA\Property(property="total", type="integer"),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="400",
     *       description="Bad request response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string")
     *       )
     *    ),
     *    @OA\Response(
     *       response="500",
     *       description="Internal server error",
     *       @OA\JsonContent(
     *          @OA\Property(property="error", type="string")
     *       )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $mongoId = $request->query('mongo_id', null);
            $teamId = $request->query('team_id', null);
            $userId = $request->query('user_id', null);
            $filterTitle = $request->query('title', null);
            $filterStatus = $request->query('status', null);
            $perPage = request('per_page', Config::get('constants.per_page'));

            $sort = $request->query('sort', 'name:desc');
            $tmp = explode(':', $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists(1, $tmp) ? $tmp[1] : 'desc';

            // Get all field names from the Tool model
            $allFields = collect(Tool::first())->keys()->toArray();

            // Validate sort field and direction
            if (!in_array($sortField, $allFields)) {
                return response()->json([
                    'message' => '"' . $sortField . '" is not a valid field to sort on'
                ], 400);
            }

            $validDirections = ['desc', 'asc'];
            if (!in_array($sortDirection, $validDirections)) {
                return response()->json([
                    "message" => 'Sort direction must be either: ' . implode(' OR ', $validDirections) .
                        '. Not "' . $sortDirection . '"'
                ], 400);
            }

            // Perform query for the matching tools with filters, sorting, and pagination
            $tools = Tool::with([
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
            ->when($mongoId, function ($query) use ($mongoId) {
                return $query->where('mongo_id', '=', $mongoId);
            })
            ->when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })
            ->when($userId, function ($query) use ($userId) {
                return $query->where('user_id', '=', $userId);
            })
            ->when(
                $filterStatus,
                function ($query) use ($filterStatus) {
                    return $query->where('status', '=', $filterStatus)
                        ->when(
                            $filterStatus === Tool::STATUS_ARCHIVED,
                            function ($query) {
                                return $query->withTrashed();
                            }
                        );
                }
            )
            ->when($filterTitle, function ($query) use ($filterTitle) {
                return $query->where('name', 'like', '%' . $filterTitle . '%');
            })
            ->where('enabled', 1)
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage, ['*'], 'page');

            // Transform collection to include datasets
            $tools->map(function ($tool) {
                $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
                return $tool;
            });

            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool get all',
            ]);

            return response()->json(
                $tools
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/tools/count/{field}",
     *    operationId="count_unique_fields_tools",
     *    tags={"Tools"},
     *    summary="ToolController@count",
     *    description="Get Counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
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
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
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
    public function count(Request $request, string $field): JsonResponse
    {
        try {
            $teamId = $request->query('team_id', null);
            $userId = $request->query('user_id', null);
            $counts = Tool::when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })->when($userId, function ($query) use ($userId) {
                return $query->where('user_id', '=', $userId);
            })->withTrashed()
                ->select($field)
                ->get()
                ->groupBy($field)
                ->map->count();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool count",
            ]);

            return response()->json([
                "data" => $counts
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/tools/{id}",
     *    operationId="fetch_tools",
     *    tags={"Tools"},
     *    summary="ToolController@show",
     *    description="Get tool by id",
     *    security={{"bearerAuth":{}}},
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
     *          @OA\Property( property="message", type="string", example="success" ),
     *          @OA\Property( property="data", type="array", example="[]", @OA\Items( type="array", @OA\Items() ) ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       )
     *    )
     * )
     */
    public function show(GetTool $request, int $id): JsonResponse
    {
        list($userId, $teamId) = $this->getAccessorUserAndTeam($request);

        $viewType = $request->query('view_type', 'full');
        $trimmed = $viewType === 'mini';

        try {
            $tool = $this->getToolById($id, true, $trimmed);

            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
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
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/tools",
     *    operationId="create_tools",
     *    tags={"Tools"},
     *    summary="ToolController@store",
     *    description="Create a new tool",
     *    security={{"bearerAuth":{}}},
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
     *      @OA\Response(
     *          response=201,
     *          description="Created",
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
     */
    public function store(CreateTool $request): JsonResponse
    {
        list($userId, $teamId) = $this->getAccessorUserAndTeam($request);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $currentUser = isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId;

        if (!is_null($teamId)) {
            $this->checkAccess($input, $teamId, null, 'team', $request->header());
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
                'team_id',
                'mongo_id',
                'associated_authors',
                'contact_address',
                'any_dataset',
                'status',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            $array['name'] = formatCleanInput($input['name']);
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
            $this->checkPublications($toolId, $publications, $currentUser);

            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$toolId);
            }

            $collections = array_key_exists('collections', $input) ? $input['collections'] : [];
            $this->checkCollections($toolId, $collections, $currentUser);

            $currentTool = Tool::where('id', $toolId)->first();
            if ($currentTool->status === Tool::STATUS_ACTIVE) {
                $this->indexElasticTools((int) $toolId);
            }

            Auditor::log([
                'user_id' => $currentUser,
                'team_id' => $teamId,
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
                'user_id' => $currentUser,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/tools/{id}",
     *    operationId="update_tools",
     *    tags={"Tools"},
     *    summary="ToolController@update",
     *    description="Update tool by id",
     *    security={{"bearerAuth":{}}},
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
     */
    public function update(UpdateTool $request, int $id): JsonResponse
    {
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $currentUser = isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId;
        $initTool = Tool::withTrashed()->where('id', $id)->first();

        if ($createOrigin === 'API') {
            $this->checkAccess($input, $initTool->team_id, null, 'team', $request->header());
        } else {
            $this->checkAccess($input, null, $initTool->user_id, 'user');
        }

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
                'team_id',
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

            $array['user_id'] = $userId ?? $jwtUser['id'];
            $array['team_id'] = $teamId;

            Tool::where('id', $id)->first()->update($array);

            ToolHasTag::where('tool_id', $id)->delete();
            $this->insertToolHasTag($input['tag'], (int)$id);

            if (array_key_exists('dataset', $input)) {
                DatasetVersionHasTool::where('tool_id', $id)->delete();
                $this->insertDatasetVersionHasTool($input['dataset'], (int)$id);
            }
            ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
            if (array_key_exists('programming_language', $input)) {
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int)$id);
            }
            ToolHasProgrammingPackage::where('tool_id', $id)->delete();
            if (array_key_exists('programming_package', $input)) {
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int)$id);
            }
            ToolHasTypeCategory::where('tool_id', $id)->delete();
            if (array_key_exists('type_category', $input)) {
                $this->insertToolHasTypeCategory($input['type_category'], (int)$id);
            }

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($id, $publications, $currentUser);

            DurHasTool::where('tool_id', $id)->delete();
            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$id);
            }

            $collections = array_key_exists('collections', $input) ? $input['collections'] : [];
            $this->checkCollections($id, $collections, $currentUser);

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
                'user_id' => $currentUser,
                'team_id' => $teamId,
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
                'user_id' => $currentUser,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/tools/{id}",
     *    operationId="edit_tools",
     *    tags={"Tools"},
     *    summary="ToolController@edit",
     *    description="Edit tool by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="unarchive",
     *       in="query",
     *       description="Unarchive a tool",
     *       @OA\Schema(
     *          type="string",
     *          description="instruction to unarchive tool",
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
     */
    public function edit(EditTool $request, int $id): JsonResponse
    {
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $currentUser = isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId;
        $toolModel = Tool::withTrashed()->where('id', $id)->first();

        if ($createOrigin === 'API') {
            $this->checkAccess($input, $toolModel->team_id, null, 'team', $request->header());
        } else {
            $this->checkAccess($input, null, $toolModel->user_id, 'user');
        }

        try {
            if ($request->has('unarchive')) {
                // Restore the tool and related models
                $toolModel = Tool::withTrashed()
                    ->find($id);
                if (($request['status'] !== Tool::STATUS_ARCHIVED) && (in_array($request['status'], [
                    Tool::STATUS_ACTIVE, Tool::STATUS_DRAFT
                ]))) {
                    $toolModel->status = $request['status'];
                    $toolModel->deleted_at = null;
                    $toolModel->save();

                    ToolHasTag::withTrashed()->where('tool_id', $id)->restore();
                    DatasetVersionHasTool::withTrashed()->where('tool_id', $id)->restore();
                    ToolHasProgrammingLanguage::withTrashed()->where('tool_id', $id)->restore();
                    ToolHasProgrammingPackage::withTrashed()->where('tool_id', $id)->restore();
                    ToolHasTypeCategory::withTrashed()->where('tool_id', $id)->restore();
                    PublicationHasTool::withTrashed()->where('tool_id', $id)->restore();
                    DurHasTool::withTrashed()->where('tool_id', $id)->restore();
                    CollectionHasTool::withTrashed()->where('tool_id', $id)->restore();

                    Auditor::log([
                        'user_id' => $currentUser,
                        'team_id' => $teamId,
                        'action_type' => 'UPDATE',
                        'action_name' => class_basename($this) . '@'.__FUNCTION__,
                        'description' => "Tool " . $id . " unarchived",
                    ]);
                } else {
                    throw new Exception('Cannot unarchive current tool because valid status not supplied');
                }
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
                'team_id',
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

            $array['user_id'] = $userId ?? $jwtUser['id'];
            $array['team_id'] = $teamId;

            $initTool = Tool::withTrashed()->where('id', $id)->first();

            if ($initTool['status'] === Tool::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current tool! Status already "ARCHIVED"');
            }

            Tool::where('id', $id)->update($array);

            if (array_key_exists('tag', $input)) {
                ToolHasTag::where('tool_id', $id)->delete();
                $this->insertToolHasTag($input['tag'], (int)$id);
            };

            if (array_key_exists('dataset', $input)) {
                DatasetVersionHasTool::where('tool_id', $id)->delete();
                $this->insertDatasetVersionHasTool($input['dataset'], (int)$id);
            }

            if (array_key_exists('programming_language', $input)) {
                ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int)$id);
            }
            if (array_key_exists('programming_package', $input)) {
                ToolHasProgrammingPackage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int)$id);
            }
            if (array_key_exists('type_category', $input)) {
                ToolHasTypeCategory::where('tool_id', $id)->delete();
                $this->insertToolHasTypeCategory($input['type_category'], (int)$id);
            }

            if (array_key_exists('publications', $input)) {
                $publications = $input['publications'];
                $this->checkPublications($id, $publications, $currentUser);
            }

            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$id);
            }

            if (array_key_exists('collections', $input)) {
                $collections = $input['collections'];
                $this->checkCollections($id, $collections, $currentUser);
            }

            $currentTool = Tool::where('id', $id)->first();
            if ($currentTool->status === Tool::STATUS_ACTIVE) {
                $this->indexElasticTools($id);
            }

            Auditor::log([
                'user_id' => $currentUser,
                'team_id' => $teamId,
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
                'user_id' => $currentUser,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/tools/{id}",
     *    operationId="delete_tools",
     *    tags={"Tools"},
     *    summary="ToolController@destroy",
     *    description="Delete tool by id",
     *    security={{"bearerAuth":{}}},
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
     */
    public function destroy(DeleteTool $request, int $id): JsonResponse
    {
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $tool = Tool::where(['id' => $id])->first();

        if ($createOrigin === 'API') {
            $this->checkAccess($input, $tool->team_id, null, 'team', $request->header());
        } else {
            $this->checkAccess($input, null, $tool->user_id, 'user');
        }

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
                    'user_id' => isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId,
                    'team_id' => $teamId,
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
                'user_id' => isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function getToolById(int $toolId, bool $onlyActive = false, bool $trimmed = false)
    {
        $tool = Tool::with([
            'user',
            'tag',
            'team',
            'license',
            'programmingLanguages',
            'programmingPackages',
            'typeCategory',
            'publications' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Publication::STATUS_ACTIVE);
                }
            },
            'durs' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Dur::STATUS_ACTIVE);
                }
            },
            'collections' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', CollectionModel::STATUS_ACTIVE);
                }
            },
            'category',
            'versions' => function ($query) use ($trimmed) {
                $query->whereHas('dataset', fn ($q) => $q->where('status', 'ACTIVE'));
                $query->when($trimmed, function ($q) {
                    $q->selectRaw('
                        dataset_versions.id,dataset_versions.dataset_id,
                        short_title as shortTitle,
                        CONVERT(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.populationSize")), SIGNED) as populationSize,
                        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.datasetType")) as datasetType,
                        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.publisher.name")) as dataCustodian
                    ');
                });
            },
        ])
        ->withTrashed()
        ->where(['id' => $toolId])
        ->first();

        $tool->name = html_entity_decode($tool->name);
        $tool->description = html_entity_decode($tool->description);
        $tool->results_insights = html_entity_decode($tool->results_insights);
        $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
        return $tool;
    }

    /**
     * Creates a new tag if it doesn't exist.
     *
     * @param mixed $value
     * @return mixed
     */
    private function createNewTagIfNotExists(mixed $value)
    {
        if (!is_numeric($value) && !empty($value)) {
            $tag = Tag::where([
                'description' => $value,
            ])->first();

            if (is_null($tag)) {
                $createdTag = Tag::create([
                    'type' => TagType::TOPICS,
                    'description' => $value,
                    'enabled' => true,
                ]);

                return $createdTag->id;
            } else {
                return $tag->id;
            }
        }

        return null;
    }

    /**
     * Insert data into ToolHasTag
     *
     * @param array $tags
     * @param integer $toolId
     * @return mixed
     */
    private function insertToolHasTag(array $tags, int $toolId): mixed
    {
        try {
            foreach ($tags as $value) {
                if ($value === 0) {
                    continue;
                }

                $createdTagId = $this->createNewTagIfNotExists($value);

                // This whole thing could be an updateOrCreate, but Eloquent can't cope with the fact
                // this model has no single primary key column so we have to go around the houses.

                if (is_null($createdTagId)) {
                    $toolHasTag = ToolHasTag::withTrashed()->where(
                        [
                        'tool_id' => (int)$toolId,
                        'tag_id' => (int)$value,
                        ]
                    )->first();

                    // undelete if it has been soft-deleted
                    if ($toolHasTag && $toolHasTag->deleted_at != null) {
                        // We have to use a raw query to undelete because Eloquent can't cope with the fact this model has no single primary key column.
                        \DB::table('tool_has_tags')
                        ->where(['tool_id' => (int)$toolId,
                            'tag_id' => (int)$value])
                            ->update(['deleted_at' => null]);
                    };
                } else {
                    $toolHasTag = false;
                }

                // create it if required
                if (!$toolHasTag) {
                    ToolHasTag::create([
                        'tool_id' => (int)$toolId,
                        'tag_id' => $createdTagId ? $createdTagId : (int)$value,
                    ]);
                }
            }

            return true;
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
     * Insert data into DatasetVersionHasTool
     *
     * @param array $datasetIds
     * @param integer $toolId
     * @return mixed
     */
    private function insertDatasetVersionHasTool(array $datasetIds, int $toolId): mixed
    {
        try {
            foreach ($datasetIds as $value) {
                if (is_array($value)) {
                    $datasetVersionIDs = DatasetVersion::where('dataset_id', $value['id'])->pluck('id')->all();

                    foreach ($datasetVersionIDs as $datasetVersionID) {
                        DatasetVersionHasTool::withTrashed()->updateOrCreate([
                            'tool_id' => $toolId,
                            'dataset_version_id' => $datasetVersionID,
                            'link_type' => $value['link_type'],
                            'deleted_at' => null,
                        ]);
                    }
                } else {
                    $datasetVersionIDs = DatasetVersion::where('dataset_id', $value)->pluck('id')->all();

                    foreach ($datasetVersionIDs as $datasetVersionID) {
                        DatasetVersionHasTool::withTrashed()->updateOrCreate([
                            'tool_id' => $toolId,
                            'dataset_version_id' => $datasetVersionID,
                            'deleted_at' => null,
                        ]);
                    }
                }
            }
            return true;
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
     * Insert data into ToolHasProgrammingLanguage
     *
     * @param array $programmingLanguages
     * @param integer $toolId
     * @return mixed
     */
    private function insertToolHasProgrammingLanguage(array $programmingLanguages, int $toolId): mixed
    {
        try {
            foreach ($programmingLanguages as $value) {
                ToolHasProgrammingLanguage::withTrashed()->updateOrCreate(
                    [
                    'tool_id' => (int)$toolId,
                    'programming_language_id' => (int)$value,
                    ],
                    ['deleted_at' => null]
                );
            }

            return true;
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
     * Insert data into ToolHasProgrammingPackage
     *
     * @param array $programmingPackages
     * @param integer $toolId
     * @return mixed
     */
    private function insertToolHasProgrammingPackage(array $programmingPackages, int $toolId): mixed
    {
        try {
            foreach ($programmingPackages as $value) {
                ToolHasProgrammingPackage::withTrashed()->updateOrCreate(
                    [
                    'tool_id' => (int)$toolId,
                    'programming_package_id' => (int)$value,
                    ],
                    ['deleted_at' => null]
                );
            }

            return true;
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
     * Insert data into ToolHasTypeCategory
     *
     * @param array $typeCategories
     * @param integer $toolId
     * @return mixed
     */
    private function insertToolHasTypeCategory(array $typeCategories, int $toolId): mixed
    {
        try {
            foreach ($typeCategories as $value) {
                ToolHasTypeCategory::withTrashed()->updateOrCreate(
                    [
                    'tool_id' => (int)$toolId,
                    'type_category_id' => (int)$value,
                    ],
                    ['deleted_at' => null]
                );
            }

            return true;
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
     * Insert data into DurHasTool
     *
     * @param array $durs
     * @param integer $toolId
     * @return mixed
     */
    private function insertDurHasTool(array $durs, int $toolId): mixed
    {
        try {
            $cols = DurHasTool::where(['tool_id' => $toolId])->get();
            foreach ($cols as $col) {
                if (!in_array($col->dur_id, $durs)) {
                    DurHasTool::where([
                        'tool_id' => (int)$toolId,
                        'dur_id' => (int)$col->dur_id,
                    ])->delete();
                } else {
                    DurHasTool::updateOrCreate([
                        'tool_id' => (int)$toolId,
                        'dur_id' => (int)$col->dur_id,
                    ]);
                }
            }
            foreach ($durs as $value) {
                DurHasTool::updateOrCreate([
                    'tool_id' => (int)$toolId,
                    'dur_id' => (int)$value,
                ]);
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // publications
    private function checkPublications(int $toolId, array $inPublications, ?int $userId = null)
    {
        $pubs = PublicationHasTool::where(['tool_id' => $toolId])->get();
        foreach ($pubs as $pub) {
            if (!in_array($pub->publication_id, $this->extractInputIdToArray($inPublications))) {
                $this->deletePublicationHasTools($toolId, $pub->publication_id);
            }
        }

        foreach ($inPublications as $publication) {
            $checking = $this->checkInPublicationHasTools($toolId, (int)$publication['id']);

            if (!$checking) {
                $this->addPublicationHasTool($toolId, $publication, $userId);
            }
        }
    }

    private function addPublicationHasTool(int $toolId, array $publication, ?int $userId = null)
    {
        try {
            $arrCreate = [
                'tool_id' => $toolId,
                'publication_id' => $publication['id'],
            ];

            if (array_key_exists('user_id', $publication)) {
                $arrCreate['user_id'] = (int)$publication['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $publication)) {
                $arrCreate['reason'] = $publication['reason'];
            }

            if (array_key_exists('updated_at', $publication)) { // special for migration
                $arrCreate['created_at'] = $publication['updated_at'];
                $arrCreate['updated_at'] = $publication['updated_at'];
            }

            return PublicationHasTool::updateOrCreate(
                $arrCreate,
                [
                    'tool_id' => $toolId,
                    'publication_id' => $publication['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addPublicationHasTool :: ' . $e->getMessage());
        }
    }

    private function checkInPublicationHasTools(int $toolId, int $publicationId)
    {
        try {
            return PublicationHasTool::where([
                'tool_id' => $toolId,
                'publication_id' => $publicationId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInPublicationHasTools :: ' . $e->getMessage());
        }
    }

    private function deletePublicationHasTools(int $toolId, int $publicationId)
    {
        try {
            return PublicationHasTool::where([
                'tool_id' => $toolId,
                'publication_id' => $publicationId,
            ])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deletePublicationHasTools :: ' . $e->getMessage());
        }
    }

    // collections
    private function checkCollections(int $toolId, array $inCollections, ?int $userId = null)
    {
        $colls = CollectionHasTool::where(['tool_id' => $toolId])->get();
        foreach ($colls as $coll) {
            if (!in_array($coll->collection_id, $this->extractInputIdToArray($inCollections))) {
                $this->deleteCollectionHasTools($toolId, $coll->collection_id);
            }
        }

        foreach ($inCollections as $collection) {
            $checking = $this->checkInCollectionHasTools($toolId, (int) $collection['id']);

            if (!$checking) {
                $this->addCollectionHasTool($toolId, $collection, $userId);
            }
        }
    }

    private function addCollectionHasTool(int $toolId, array $collection, ?int $userId = null)
    {
        try {
            $arrCreate = [
                'tool_id' => $toolId,
                'collection_id' => $collection['id'],
            ];

            if (array_key_exists('user_id', $collection)) {
                $arrCreate['user_id'] = (int)$collection['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $collection)) {
                $arrCreate['reason'] = $collection['reason'];
            }

            if (array_key_exists('updated_at', $collection)) { // special for migration
                $arrCreate['created_at'] = $collection['updated_at'];
                $arrCreate['updated_at'] = $collection['updated_at'];
            }

            return CollectionHasTool::updateOrCreate(
                $arrCreate,
                [
                    'tool_id' => $toolId,
                    'collection_id' => $collection['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasTool :: ' . $e->getMessage());
        }
    }

    private function checkInCollectionHasTools(int $toolId, int $collectionId)
    {
        try {
            return CollectionHasTool::where([
                'tool_id' => $toolId,
                'collection_id' => $collectionId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollecionHasTools :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasTools(int $toolId, int $collectionId)
    {
        try {
            return CollectionHasTool::where([
                'tool_id' => $toolId,
                'collection_id' => $collectionId,
            ])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasTools :: ' . $e->getMessage());
        }
    }

    private function extractInputIdToArray(array $input): array
    {
        $response = [];
        foreach ($input as $value) {
            $response[] = $value['id'];
        }

        return $response;
    }

}
