<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Throwable;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Requests\TagRequest;
use App\Http\Controllers\Controller;

class TagController extends Controller
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
     *    path="/api/v1/tags/{id?}",
     *    operationId="fetch",
     *    tags={"Tags"},
     *    summary="TagController@show",
     *    description="Get Tags or Get Tag by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tag id",
     *       required=false,
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
     * 
     * Get Tags / Get Tag by id
     *
     * @param Request $request
     * @param integer|null $id
     * @return mixed
     */
    public function show(Request $request, int $id = null): mixed
    {

        if($id) {
            $tags = Tag::where('id', $id)->get();
        } else {
            $tags = Tag::all();
        }

        return response()->json([
            'data' => $tags
        ], 200);
    }

    /**
     * @OA\Post(
     *    path="/api/v1/tags",
     *    operationId="create",
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
     *             @OA\Property(
     *                property="type",
     *                type="string",
     *                example="features",
     *             ),
     *          ),
     *       ),
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
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="401",
     *       description="Missing Property",
     *    ),
     * )
     * 
     * Create a new tag
     *
     * @param TagRequest $request
     * @return mixed
     */
    public function store(TagRequest $request): mixed
    {
        try {
            $input = $request->all();

            $tag = Tag::create($input);

            return response()->json([
                'data' => $tag
            ], 201);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/tags",
     *    operationId="update",
     *    tags={"Tags"},
     *    summary="TagController@store",
     *    description="Update tag",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="enabled",
     *                type="boolean",
     *                example=true,
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="Resource was updated with success.",
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="400",
     *       description="Please provide a valid information in the request body.",
     *    ),
     *    @OA\Response(
     *       response="422",
     *       description="Resource not found.",
     *    ),
     * )
     * 
     * Update tag
     *
     * @param Request $request
     * @param integer $id
     * @return mixed
     */
    public function update(Request $request, int $id): mixed
    {
        try {
            $input = $request->all();

            if (!$input) {
                return response()->json([
                    'message' => 'Please provide a valid information in the request body.',
                ], 400);
            }

            $tags = Tag::where('id', $id)->get();

            if ($tags !== null) {
                Tag::where('id', $id)->update($input);

                return response()->json([
                    'message' => 'Resource was updated with success.',
                ], 202);
            }

            return response()->json([
                'message' => 'Resource not found.',
            ], 422);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/tags/{id}",
     *    operationId="Delete",
     *    tags={"Tags"},
     *    summary="TagController@destroy",
     *    description="Delete Tag based in id",
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
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="Resource deleted successfully.",
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=422,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="Resource not found.",
     *          ),
     *       ),
     *    ),
     * )
     * 
     * delete resource by id
     *
     * @param Request $request
     * @param string $id
     * @return mixed
     */
    public function destroy(Request $request, string $id): mixed
    {
        try {
            $tags = Tag::where('id', $id)->get();

            if ($tags) {
                Tag::where('id', $id)->delete();

                return response()->json([
                    'message' => 'Resource deleted successfully.',
                ], 200);
            }

            return response()->json([
                'message' => 'Resource not found.',
            ], 422);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/tags/{id}/restore",
     *    operationId="restore",
     *    tags={"Tags"},
     *    summary="TagController@restore",
     *    description="Restore Tag based in id",
     *    security={{"bearer":{}}},
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
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="Resource deleted successfully.",
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=422,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="Resource not found.",
     *          ),
     *       ),
     *    ),
     * )
     * 
     * restore resource by id
     *
     * @param integer $id
     */
    public function restore(int $id)
    {
        try {
            $tags = Tag::withTrashed()->where('id', $id)->get();

            if ($tags) {
                $tag = Tag::where('id', $id)->restore();

                return response()->json([
                    'message' => 'Resource restored successfully.',
                ], 200);
            }

            return response()->json([
                'message' => 'Resource not found.',
            ], 404);

        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }
}
