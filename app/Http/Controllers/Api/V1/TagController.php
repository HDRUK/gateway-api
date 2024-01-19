<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use App\Models\Tag;
use App\Http\Requests\Tag\GetTag;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Tag\EditTag;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\CreateTag;
use App\Http\Requests\Tag\DeleteTag;
use App\Http\Requests\Tag\UpdateTag;
use App\Http\Traits\RequestTransformation;

class TagController extends Controller
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
     *    path="/api/v1/tags",
     *    operationId="fetch_all_tags",
     *    tags={"Tags"},
     *    summary="TagController@index",
     *    description="Get All Tags",
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
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(): JsonResponse
    {
        $tags = Tag::where('enabled', 1)->paginate(Config::get('constants.per_page'), ['*'], 'page');

        return response()->json(
            $tags
        );
    }

    /**
     * @OA\Get(
     *    path="/api/v1/tags/{id}",
     *    operationId="fetch_tags",
     *    tags={"Tags"},
     *    summary="TagController@show",
     *    description="Get tag by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tag id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="tag id",
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
     */
    public function show(GetTag $request, int $id): JsonResponse
    {
        try {
            $tags = Tag::where([
                'id' => $id,
            ])->get();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $tags,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/tags",
     *    operationId="create_tags",
     *    tags={"Tags"},
     *    summary="TagController@store",
     *    description="Create a new tag",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="type", type="string", example="features"),
     *             @OA\Property(property="description", type="string", example="lorem ipsum"),
     *             @OA\Property(property="enabled", type="boolean", example="true")
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
    public function store(CreateTag $request): JsonResponse
    {
        try {
            $input = $request->all();

            $tag = Tag::create([
                'type' => $input['type'],
                'description' => $input['description'],
                'enabled' => $input['enabled'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $tag->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/tags/{id}",
     *    operationId="update_tags",
     *    tags={"Tags"},
     *    summary="TagController@update",
     *    description="Update tag",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tag id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="tag id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="type", type="string", example="features"),
     *             @OA\Property(property="description", type="string", example="lorem ipsum"),
     *             @OA\Property(property="enabled", type="boolean", example="true")
     *          ),
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
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="bad request"),
     *          )
     *      )
     * )
     */
    public function update(UpdateTag $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            Tag::where('id', $id)->update([
                'type' => $input['type'],
                'description' => $input['description'],
                'enabled' => $input['enabled'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Tag::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/tags/{id}",
     *    operationId="edit_tags",
     *    tags={"Tags"},
     *    summary="TagController@edit",
     *    description="Edit tag",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tag id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="tag id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="type", type="string", example="features"),
     *             @OA\Property(property="description", type="string", example="lorem ipsum"),
     *             @OA\Property(property="enabled", type="boolean", example="true")
     *          ),
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
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="bad request"),
     *          )
     *      )
     * )
     */
    public function edit(EditTag $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $arrayKeys = [
                'type',
                'description',
                'enabled',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Tag::where('id', $id)->update($array);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Tag::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/tags/{id}",
     *    operationId="delete_tags",
     *    tags={"Tags"},
     *    summary="TagController@destroy",
     *    description="Delete tag by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tag id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="tag id",
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
    public function destroy(DeleteTag $request, string $id): JsonResponse
    {
        try {
            Tag::where('id', $id)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
