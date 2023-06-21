<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;

use App\Http\Requests\EditCollection;
use App\Http\Requests\CreateCollection;
use App\Http\Requests\DeleteCollection;
use App\Http\Requests\UpdateCollection;
use App\Http\Traits\RequestTransformation;

class CollectionController extends Controller
{
    use RequestTransformation;
    
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/collections",
     *    operationId="fetch_all_collections",
     *    tags={"Collections"},
     *    summary="CollectionController@index",
     *    description="Returns a list of collections",
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="name", type="string", example="expedita"),
     *                   @OA\Property(property="description", type="string", example="Quibusdam in ducimus eos est."),
     *                   @OA\Property(property="image_link", type="string", example="https:\/\/via.placeholder.com\/640x480.png\/003333?text=animals+iusto"),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="keywords", type="string", example="minus deserunt dolorum"),
     *                   @OA\Property(property="public", type="boolean", example="0"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                )
     *             )
     *          )
     *       )
     *    )
     * )
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $tags = Collection::paginate(Config::get('constants.per_page'));

        return response()->json(
            $tags
        );
    }

    /**
     * @OA\Get(
     *    path="/api/v1/collections/{id}",
     *    operationId="fetch_collections",
     *    tags={"Collections"},
     *    summary="CollectionController@show",
     *    description="Get collection by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="collection id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="collection id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="name", type="string", example="expedita"),
     *                   @OA\Property(property="description", type="string", example="Quibusdam in ducimus eos est."),
     *                   @OA\Property(property="image_link", type="string", example="https:\/\/via.placeholder.com\/640x480.png\/003333?text=animals+iusto"),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="keywords", type="string", example="minus deserunt dolorum"),
     *                   @OA\Property(property="public", type="boolean", example="0"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     * 
     * Get Collections by id
     *
     * @param Request $request
     * @param integer $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $collections = Collection::where(['id' => $id])
                ->get();

            if ($collections->count()) {
                return response()->json([
                    'message' => 'success',
                    'data' => $collections,
                ], 200);
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/collections",
     *    operationId="create_collections",
     *    tags={"Collections"},
     *    summary="CollectionController@store",
     *    description="Create a new collection",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="name", type="string", example="covid"),
     *             @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *             @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *             @OA\Property(property="enabled", type="boolean", example="true"),
     *             @OA\Property(property="keywords", type="string", example="key words"),
     *             @OA\Property(property="public", type="boolean", example="true"),
     *             @OA\Property(property="counter", type="integer", example="123")
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=201,
     *       description="Created",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="data", type="integer", example="100")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error"),
     *       )
     *    )
     * )
     * 
     * Create a new collection
     *
     * @param CreateCollection $request
     * @return JsonResponse
     */
    public function store(CreateCollection $request): JsonResponse
    {
        try {
            $input = $request->all();

            $collection = Collection::create([
                'name' => $input['name'],
                'description' => $input['description'],
                'image_link' => $input['image_link'],
                'enabled' => $input['enabled'],
                'keywords' => $input['keywords'],
                'public' => $input['public'],
                'counter' => (int) $input['counter'],
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $collection->id,
            ], 201);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/collections/{id}",
     *    tags={"Collections"},
     *    summary="Update a collection",
     *    description="Update a collection",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="collection id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="collection id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="name", type="string", example="covid"),
     *             @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *             @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *             @OA\Property(property="enabled", type="boolean", example="true"),
     *             @OA\Property(property="keywords", type="string", example="key words"),
     *             @OA\Property(property="public", type="boolean", example="true"),
     *             @OA\Property(property="counter", type="integer", example="123"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="covid"),
     *                 @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *                 @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *                 @OA\Property(property="enabled", type="boolean", example="true"),
     *                 @OA\Property(property="keywords", type="string", example="key words"),
     *                 @OA\Property(property="public", type="boolean", example="true"),
     *                 @OA\Property(property="counter", type="integer", example="123"),
     *                 @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *              ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     *
     * @param UpdateCollection $request
     * @param integer $id
     * @return JsonResponse
     */
    public function update(UpdateCollection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            Collection::where('id', $id)->update([
                'name' => $input['name'],
                'description' => $input['description'],
                'image_link' => $input['image_link'],
                'enabled' => $input['enabled'],
                'keywords' => $input['keywords'],
                'public' => $input['public'],
                'counter' => (int) $input['counter'],
            ]);

            return response()->json([
                'message' => 'success',
                'data' => Collection::where('id', $id)->first()
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/collections/{id}",
     *    tags={"Collections"},
     *    summary="Edit a collection",
     *    description="Edit a collection",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="collection id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="collection id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="name", type="string", example="covid"),
     *             @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *             @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *             @OA\Property(property="enabled", type="boolean", example="true"),
     *             @OA\Property(property="keywords", type="string", example="key words"),
     *             @OA\Property(property="public", type="boolean", example="true"),
     *             @OA\Property(property="counter", type="integer", example="123"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="covid"),
     *                 @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *                 @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *                 @OA\Property(property="enabled", type="boolean", example="true"),
     *                 @OA\Property(property="keywords", type="string", example="key words"),
     *                 @OA\Property(property="public", type="boolean", example="true"),
     *                 @OA\Property(property="counter", type="integer", example="123"),
     *                 @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *              ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     *
     * @param EditCollection $request
     * @param integer $id
     * @return JsonResponse
     */
    public function edit(EditCollection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            $array = $this->checkEditArray($input, ['name', 'description', 'image_link', 'enabled', 'keywords', 'public', 'counter']);

            Collection::where('id', $id)->update($array);

            return response()->json([
                'message' => 'success',
                'data' => Collection::where('id', $id)->first()
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/collections/{id}",
     *    tags={"Collections"},
     *    summary="Delete a collection",
     *    description="Delete a collection",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="collection id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="collection id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error")
     *       )
     *    )
     * )
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function destroy(DeleteCollection $request, int $id): JsonResponse
    {
        try {
            $collection = Collection::findOrFail($id);
            if ($collection) {
                $collection->delete();

                return response()->json([
                    'message' => 'success',
                ], 200);
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
