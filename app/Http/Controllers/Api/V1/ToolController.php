<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\Tool;
use App\Models\ToolHasTag;
use Illuminate\Http\Request;
use App\Http\Requests\ToolRequest;
use App\Http\Controllers\Controller;

class ToolController extends Controller
{
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
     * 
     * Get All Tools
     * 
     * @return mixed
     */
    public function index(): mixed
    {
        $tools = Tool::with(['user', 'tag'])->where('enabled', 1)->get();

        return response()->json([
            'message' => 'success',
            'data' => $tools
        ], 200);
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
     * 
     * Get Tools by id
     *
     * @param Request $request
     * @param integer $id
     * @return mixed
     */
    public function show(Request $request, int $id): mixed
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
     * 
     * Create a new tool
     *
     * @param ToolRequest $request
     * @return mixed
     */
    public function store(ToolRequest $request): mixed
    {
        try {
            $input = $request->all();

            $checkToolByName = Tool::withTrashed()->where([
                'name' => $input['name'],
            ])->first();

            if ($checkToolByName) {
                return response()->json([
                    'message' => 'bad request',
                ], 400);
            }

            $arrayTool = array_filter($input, function ($key) {
                return $key !== 'tag';
            }, ARRAY_FILTER_USE_KEY);
            $arrayToolTag = $input['tag'];

            $tool = Tool::create($arrayTool);

            foreach ($arrayToolTag as $value) {
                ToolHasTag::updateOrCreate([
                    'tool_id' => (int) $tool->id,
                    'tag_id' => (int) $value,
                ]);
            }

            return response()->json([
                'message' => 'created',
                'data' => $tool->id,
            ], 201);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
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
     * 
     * Update tool
     *
     * @param ToolRequest $request
     * @param string $id
     * @return mixed
     */
    public function update(ToolRequest $request, string $id): mixed
    {
        try {
            $input = $request->all();

            if (!$input) {
                return response()->json([
                    'message' => 'bad request',
                ], 400);
            }

            $checkToolById = Tool::withTrashed()->where([
                'id' => $id,
            ])->first();

            if (!$checkToolById) {
                return response()->json([
                    'message' => 'bad request',
                ], 400);
            }

            $arrayTool = array_filter($input, function ($key) {
                return $key !== 'tag';
            }, ARRAY_FILTER_USE_KEY);
            $arrayToolTag = $input['tag'];

            Tool::withTrashed()->where('id', $id)->update($arrayTool);

            foreach ($arrayToolTag as $value) {
                ToolHasTag::updateOrCreate([
                    'tool_id' => (int) $id,
                    'tag_id' => (int) $value,
                ]);
            }

            return response()->json([
                'message' => 'success',
                'data' => Tool::with(['user', 'tag'])->withTrashed()->where('id', $id)->get()
            ], 202);
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
     * 
     * Delete tool by id
     * 
     * @param string $id
     * @return mixed
     */
    public function destroy(string $id): mixed
    {
        try {
            $tool = Tool::where([
                'id' => $id,
            ])->first();

            if ($tool) {
                Tool::where('id', $id)->delete();

                return response()->json([
                    'message' => 'success',
                ], 200);
            }

            return response()->json([
                'message' => 'not found.',
            ], 404);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
