<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\ToolHasTag;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Tool\GetTool;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tool\EditTool;
use App\Http\Requests\Tool\CreateTool;
use App\Http\Requests\Tool\DeleteTool;
use App\Http\Requests\Tool\UpdateTool;
use App\Http\Traits\RequestTransformation;
use Illuminate\Http\Request;
use MetadataManagementController AS MMC;

class ToolController extends Controller
{
    use RequestTransformation;

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
     *    summary="ToolController@index",
     *    description="Get All Tools",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent( @OA\Property( property="data", type="array", example="[]", @OA\Items( type="array", @OA\Items() ) ),
     *       ),
     *    ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $mongoId = $request->query('mongo_id', null);
            $tools = Tool::with([
                    'user', 
                    'tag',
                    'team',
                ])
                ->when($mongoId, function ($query) use ($mongoId) {
                    return $query->where('mongo_id', '=', $mongoId);
                })
                ->where('enabled', 1)
                ->paginate(Config::get('constants.per_page'), ['*'], 'page');

            return response()->json(
                $tools
            );
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
            $tools = Tool::with([
                'user', 
                'tag',
                'team',
            ])->where([
                'id' => $id,
                'enabled' => 1,
            ])->get();

            return response()->json([
                'message' => 'success',
                'data' => $tools,
            ], 200);
        } catch (Exception $e) {
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
     *             @OA\Property( property="license", type="string", example="Inventore omnis aut laudantium vel alias." ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

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
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            $tool = Tool::create($array);

            $this->insertToolHasTag($input['tag'], (int) $tool->id);

            $this->indexElasticTools($input, (int) $tool->id);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool " . $tool->id . " created",
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $tool->id,
            ], 201);
        } catch (Exception $e) {
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
     *             @OA\Property( property="license", type="string", example="Inventore omnis aut laudantium vel alias." ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
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
    public function update(UpdateTool $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

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
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Tool::withTrashed()->where('id', $id)->update($array);

            ToolHasTag::where('tool_id', $id)->delete();
            $this->insertToolHasTag($input['tag'], (int) $id);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Tool::with([
                            'user', 
                            'tag',
                            'team',
                        ])
                        ->withTrashed()
                        ->where('id', $id)
                        ->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
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
     *             @OA\Property( property="license", type="string", example="Inventore omnis aut laudantium vel alias." ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
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
    public function edit(EditTool $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
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
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Tool::withTrashed()->where('id', $id)->update($array);

            if (array_key_exists('tag', $input)) {
                ToolHasTag::where('tool_id', $id)->delete();
                $this->insertToolHasTag($input['tag'], (int) $id);
            };

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Tool::with([
                            'user', 
                            'tag',
                            'team',
                        ])
                        ->withTrashed()
                        ->where('id', $id)
                        ->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            Tool::where('id', $id)->delete();
            ToolHasTag::where('tool_id', $id)->delete();
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool " . $id . " deleted",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
                ToolHasTag::updateOrCreate([
                    'tool_id' => (int) $toolId,
                    'tag_id' => (int) $value,
                ]);
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert tool document into elastic index
     *
     * @param array $input
     * @param integer $toolId
     * @return void
     */
    private function indexElasticTools(array $input, int $toolId): void 
    {
        try {

            $tags = Tag::whereIn('id', $input['tag'])->get()->toArray();
            $tagsDescription = array();
            foreach ($tags as $t) {
                $tagsDescription[] = $t['description'];
            }

            $toIndex = [
                'name' => $input['name'],
                'description' => $input['description'],
                'tags' => $tagsDescription
            ];
            $params = [
                'index' => 'tool',
                'id' => $toolId,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];

            $client = MMC::getElasticClient();
            $client->index($params);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
