<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Tool;
use App\Models\Collection;
use App\Models\DurHasTool;
use App\Models\ToolHasTag;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
use App\Http\Requests\V2\Tool\GetTool;
use App\Http\Requests\V2\Tool\EditTool;
use App\Http\Requests\V2\Tool\CreateTool;
use App\Http\Requests\V2\Tool\DeleteTool;
use App\Http\Requests\V2\Tool\UpdateTool;
use App\Models\ToolHasProgrammingPackage;
use App\Http\Traits\RequestTransformation;
use App\Models\ToolHasProgrammingLanguage;

class ToolController extends Controller
{
    use IndexElastic;
    use RequestTransformation;
    use CheckAccess;
    use ToolsV2Helper;

    /**
     * constructor method
     */
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v2/tools",
     *    operationId="fetch_all_tools_v2",
     *    tags={"Tools"},
     *    summary="Fetch all tools",
     *    description="Get all tools with optional filters and sorting",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="name",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter tools by name"
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
            $filterName = $request->query('name', null);
            $filterStatus = $request->query('status', null);
            $perPage = request('per_page', Config::get('constants.per_page'));

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
            ->when(
                $filterStatus,
                function ($query) use ($filterStatus) {
                    return $query->where('status', '=', $filterStatus);
                }
            )
            ->when($filterName, function ($query) use ($filterName) {
                return $query->where('name', 'like', '%' . $filterName . '%');
            })
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
     *    path="/api/v2/tools/count/{field}",
     *    operationId="count_unique_fields_tools_v2",
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
            $counts = Tool::select($field)
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
     *    path="/api/v2/tools/{id}",
     *    operationId="fetch_tools_v2",
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
        try {
            $tool = $this->getToolById($id, true);

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
     *    path="/api/v2/tools",
     *    operationId="create_tools_v2",
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $teamId = array_key_exists('team_id', $input) ? $input['team_id'] : null;
        if (!is_null($teamId)) {
            $this->checkAccess($input, $teamId, null, 'team');
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
            $this->checkPublications($toolId, $publications, (int)$jwtUser['id']);

            if (array_key_exists('durs', $input)) {
                $this->insertDurHasTool($input['durs'], (int)$toolId);
            }

            $collections = array_key_exists('collections', $input) ? $input['collections'] : [];
            $this->checkCollections($toolId, $collections, (int)$jwtUser['id']);

            $currentTool = Tool::where('id', $toolId)->first();
            if ($currentTool->status === Tool::STATUS_ACTIVE) {
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
     *    path="/api/v2/tools/{id}",
     *    operationId="update_tools_v2",
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initTool = Tool::where('id', $id)->first();
        $this->checkAccess($input, null, $initTool->user_id, 'user');

        try {

            if ($initTool['status'] === Tool::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current tool! Status already "ARCHIVED"');
            }

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

            Tool::where('id', $id)->first()->update($array);

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
     *    path="/api/v2/tools/{id}",
     *    operationId="edit_tools_v2",
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $toolModel = Tool::where('id', $id)->first();
        $this->checkAccess($input, null, $toolModel->user_id, 'user');

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
            $initTool = Tool::where('id', $id)->first();

            if ($initTool['status'] === Tool::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current tool! Status already "ARCHIVED"');
            }

            Tool::where('id', $id)->update($array);

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
            if ($currentTool->status === Tool::STATUS_ACTIVE) {
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
     *    path="/api/v2/tools/{id}",
     *    operationId="delete_tools_v2",
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $tool = Tool::where(['id' => $id])->first();
        $this->checkAccess($input, null, $tool->user_id, 'user');

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
