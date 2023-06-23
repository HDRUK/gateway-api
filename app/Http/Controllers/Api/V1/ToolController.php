<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use App\Models\Tool;
use App\Models\ToolHasTag;
use Illuminate\Http\Request;
use App\Http\Requests\EditTool;
use App\Http\Requests\CreateTool;
use App\Http\Requests\DeleteTool;
use App\Http\Requests\UpdateTool;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;

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
    public function index(): JsonResponse
    {
        $tools = Tool::with(['user', 'tag'])->where('enabled', 1)->paginate(Config::get('constants.per_page'));

        return response()->json(
            $tools
        );
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
    public function show(Request $request, int $id): JsonResponse
    {
        $tags = Tool::with(['user', 'tag'])->where([
            'id' => $id,
            'enabled' => 1,
        ])->get();

        if ($tags->count()) {
            return response()->json([
                'message' => 'success',
                'data' => $tags,
            ], 200);
        }

        return response()->json([
            'message' => 'not found',
        ], 404);
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
     *             @OA\Property( property="user_id", type="integer", example=1 ),
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

            $tool = Tool::create([
                'mongo_object_id' => $input['mongo_object_id'],
                'name' => $input['name'],
                'url' => $input['url'],
                'description' => $input['description'],
                'license' => $input['license'],
                'tech_stack' =>  $input['tech_stack'],
                'user_id' => $input['user_id'],
                'enabled' => $input['enabled'],
            ]);

            $this->insertToolHasTag($input['tag'], (int) $tool->id);

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
     *             @OA\Property( property="user_id", type="integer", example=1 ),
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

            Tool::withTrashed()->where('id', $id)->update([
                'mongo_object_id' => $input['mongo_object_id'],
                'name' => $input['name'],
                'url' => $input['url'],
                'description' => $input['description'],
                'license' => $input['license'],
                'tech_stack' =>  $input['tech_stack'],
                'user_id' => $input['user_id'],
                'enabled' => $input['enabled'],
            ]);

            ToolHasTag::where('tool_id', $id)->delete();
            $this->insertToolHasTag($input['tag'], (int) $id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Tool::with(['user', 'tag'])->withTrashed()->where('id', $id)->first(),
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
     *             @OA\Property( property="user_id", type="integer", example=1 ),
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
            $arrayKeys = [
                'mongo_object_id',
                'name',
                'url',
                'description',
                'license',
                'tech_stack',
                'user_id',
                'enabled',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Tool::withTrashed()->where('id', $id)->update($array);

            if (array_key_exists('tag', $input)) {
                ToolHasTag::where('tool_id', $id)->delete();
                $this->insertToolHasTag($input['tag'], (int) $id);
            };

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Tool::with(['user', 'tag'])->withTrashed()->where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/tools/{id}",
     *    operationId="delete_tools",
     *    tags={"Tags"},
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
            Tool::where('id', $id)->delete();

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
}
