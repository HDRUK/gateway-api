<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\Keyword;
use App\Models\Collection;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\CollectionHasDataset;
use App\Models\CollectionHasKeyword;
use App\Exceptions\NotFoundException;
use MetadataManagementController AS MMC;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\Collection\GetCollection;
use App\Http\Requests\Collection\EditCollection;
use App\Http\Requests\Collection\CreateCollection;
use App\Http\Requests\Collection\DeleteCollection;
use App\Http\Requests\Collection\UpdateCollection;

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
     *          @OA\Property(property="data", type="array",
     *             @OA\Items(
     *                @OA\Property(property="id", type="integer", example="123"),
     *                @OA\Property(property="name", type="string", example="expedita"),
     *                @OA\Property(property="description", type="string", example="Quibusdam in ducimus eos est."),
     *                @OA\Property(property="image_link", type="string", example="https:\/\/via.placeholder.com\/640x480.png\/003333?text=animals+iusto"),
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="public", type="boolean", example="0"),
     *                @OA\Property(property="counter", type="integer", example="34319"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *             ),
     *          ),
     *          @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *          @OA\Property(property="from", type="integer", example="1"),
     *          @OA\Property(property="last_page", type="integer", example="1"),
     *          @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *          @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *          @OA\Property(property="next_page_url", type="string", example="null"),
     *          @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections"),
     *          @OA\Property(property="per_page", type="integer", example="25"),
     *          @OA\Property(property="prev_page_url", type="string", example="null"),
     *          @OA\Property(property="to", type="integer", example="3"),
     *          @OA\Property(property="total", type="integer", example="3"),
     *       )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->has('perPage') ? (int) $request->get('perPage') : Config::get('constants.per_page');
            $collections = Collection::with([
                    'datasets',
                    'users' => function ($query) {
                        $query->distinct('id');
                    },
                    'keywords',
                    'applications' => function ($query) {
                        $query->distinct('id');
                    },
                    'team',
                ])->paginate((int) $perPage, ['*'], 'page');

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection get all",
            ]);

            return response()->json(
                $collections
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
     *                   @OA\Property(property="public", type="boolean", example="0"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function show(GetCollection $request, int $id): JsonResponse
    {
        try {
            $collections = Collection::where(['id' => $id])
                ->with([
                    'datasets', 
                    'users' => function ($query) {
                        $query->distinct('id');
                    }, 
                    'keywords',
                    'applications' => function ($query) {
                        $query->distinct('id');
                    },
                    'team',
                ])->get();

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection get " . $id,
            ]);
    
            return response()->json([
                'message' => 'success',
                'data' => $collections,
            ], 200);

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
     *             @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="public", type="boolean", example="true"),
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
     */
    public function store(CreateCollection $request): JsonResponse
    {
        try {
            $input = $request->all();

            $userId = null;
            $appId = null;
            if (array_key_exists('user_id', $input)) {
                $userId = (int) $input['user_id'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int) $input['jwt_user']['id'];
            } elseif (array_key_exists('app_user', $input)) {
                $appId = (int) $input['app']['id'];
                $app = Application::where(['id' => $appId])->first();
                $userId = (int) $app->user_id;
            }
 
            $arrayKeys = [
                'name', 
                'description', 
                'image_link', 
                'enabled', 
                'public', 
                'counter', 
                'mongo_object_id', 
                'mongo_id',
                'team_id',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            $collection = Collection::create($array);
            $collectionId = (int) $collection->id;
            
            $array['user_id'] = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($collectionId, $datasets, $array['user_id'], $appId);

            $keywords = array_key_exists('keywords', $input) ? $input['keywords'] : [];
            $this->checkKeywords($collectionId, $keywords);

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Collection::where('id', $collectionId)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Collection::where('id', $collectionId)->update(['updated_at' => $input['updated_at']]);
            }

            // updated_on
            if (array_key_exists('updated_on', $input)) {
                Collection::where('id', $collectionId)->update(['updated_on' => $input['updated_on']]);
            }

            // add in a team
            if (array_key_exists('team_id', $input)) {
                Collection::where('id', $collectionId)->update(['team_id' => $input['team_id']]);
            }
            $this->indexElasticCollections((int)$collectionId);

            Auditor::log([
                'user_id' => $array['user_id'],
                'target_team_id' => array_key_exists('team_id', $array) ? $array['team_id'] : NULL,
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection " . $collectionId . " created",
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $collectionId,
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
     *             @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="public", type="boolean", example="true"),
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
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="name", type="string", example="expedita"),
     *                   @OA\Property(property="description", type="string", example="Quibusdam in ducimus eos est."),
     *                   @OA\Property(property="image_link", type="string", example="https:\/\/via.placeholder.com\/640x480.png\/003333?text=animals+iusto"),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="public", type="boolean", example="0"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
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
     */
    public function update(UpdateCollection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            $userId = null;
            $appId = null;
            if (array_key_exists('user_id', $input)) {
                $userId = (int) $input['user_id'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int) $input['jwt_user']['id'];
            } elseif (array_key_exists('app_user', $input)) {
                $appId = (int) $input['app']['id'];
                $app = Application::where(['id' => $appId])->first();
                $userId = (int) $app->user_id;
            }

            $arrayKeys = [
                'name', 
                'description', 
                'image_link', 
                'enabled', 
                'public', 
                'counter', 
                'mongo_object_id', 
                'mongo_id',
                'team_id',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            Collection::where('id', $id)->update($array);
            $array['user_id'] = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($id, $datasets, $array['user_id'], $appId);

            $keywords = array_key_exists('keywords', $input) ? $input['keywords'] : [];
            $this->checkKeywords($id, $keywords);

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Collection::where('id', $id)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Collection::where('id', $id)->update(['updated_at' => $input['updated_at']]);
            }

            // updated_on
            if (array_key_exists('updated_on', $input)) {
                Collection::where('id', $id)->update(['updated_on' => $input['updated_on']]);
            }

            // add in a team
            if (array_key_exists('team_id', $input)) {
                Collection::where('id', $id)->update(['team_id' => $input['team_id']]);
            }
            $this->indexElasticCollections($id);

            Auditor::log([
                'user_id' => $array['user_id'],
                'target_team_id' => array_key_exists('team_id', $array) ? $array['team_id'] : NULL,
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection " . $id . " updated",
            ]);

            return response()->json([
                'message' => 'success',
                'data' => Collection::where('id', $id)->with([
                        'datasets',
                        'users' => function ($query) {
                            $query->distinct('id');
                        }, 
                        'keywords',
                        'applications' => function ($query) {
                            $query->distinct('id');
                        },
                        'team',
                    ])->first(),
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
     *             @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="public", type="boolean", example="true"),
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
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="name", type="string", example="expedita"),
     *                   @OA\Property(property="description", type="string", example="Quibusdam in ducimus eos est."),
     *                   @OA\Property(property="image_link", type="string", example="https:\/\/via.placeholder.com\/640x480.png\/003333?text=animals+iusto"),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="public", type="boolean", example="0"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
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
     */
    public function edit(EditCollection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            $userId = null;
            $appId = null;
            if ($request->has('userId')) {
                $userId = (int) $input['userId'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int) $input['jwt_user']['id'];
            } elseif (array_key_exists('app_user', $input)) {
                $appId = (int) $input['app']['id'];
            }

            $arrayKeys = [
                'name', 
                'description', 
                'image_link', 
                'enabled', 
                'public', 
                'counter', 
                'mongo_object_id', 
                'mongo_id',
                'team_id',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            Collection::where('id', $id)->update($array);
            $userIdFinal = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;

            if (array_key_exists('datasets', $input)) {
                $datasets = $input['datasets'];
                $this->checkDatasets($id, $datasets, $userIdFinal, $appId);
            }

            if (array_key_exists('keywords', $input)) {
                $keywords = $input['keywords'];
                $this->checkKeywords($id, $keywords);
            }

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Collection::where('id', $id)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Collection::where('id', $id)->update(['updated_at' => $input['updated_at']]);
            }

            // add in a team
            if (array_key_exists('team_id', $input)) {
                Collection::where('id', $id)->update(['team_id' => $input['team_id']]);
            }
            $this->indexElasticCollections($id);

            Auditor::log([
                'user_id' => $userIdFinal,
                'target_team_id' => array_key_exists('team_id', $array) ? $array['team_id'] : NULL,
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection " . $id . " updated",
            ]);

            return response()->json([
                'message' => 'success',
                'data' => Collection::where('id', $id)->with([
                    'datasets',
                    'users' => function ($query) {
                        $query->distinct('id');
                    },
                    'keywords',
                    'applications' => function ($query) {
                        $query->distinct('id');
                    },
                    'team',
                ])->first(),
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
     */
    public function destroy(DeleteCollection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            CollectionHasDataset::where(['collection_id' => $id])->delete();
            CollectionHasKeyword::where(['collection_id' => $id])->delete();
            Collection::where(['id' => $id])->delete();

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection " . $id . " deleted",
            ]);

            return response()->json([
                'message' => 'success',
            ], 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    private function checkDatasets(int $collectionId, array $inDatasets, int $userId = null, int $appId = null) 
    {
        $cols = CollectionHasDataset::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            if (!in_array($col->dataset_id, $this->extractInputDatasetIdToArray($inDatasets))) {
                $this->deleteCollectionHasDatasets($collectionId, $col->dataset_id);
            }
        }

        foreach ($inDatasets as $dataset) {
            $checking = $this->checkInCollectionHasDatasets($collectionId, (int) $dataset['id']);

            if (!$checking) {
                $this->addCollectionHasDataset($collectionId, $dataset, $userId, $appId);
            }

            MMC::reindexElastic($dataset['id']);
        }
    }

    private function addCollectionHasDataset(int $collectionId, array $dataset, int $userId = null, int $appId = null)
    {
        try {
            $arrCreate = [
                'collection_id' => $collectionId,
                'dataset_id' => $dataset['id'],
            ];

            if (array_key_exists('user_id', $dataset)) {
                $arrCreate['user_id'] = (int) $dataset['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $dataset)) {
                $arrCreate['reason'] = $dataset['reason'];
            }

            if (array_key_exists('updated_at', $dataset)) { // special for migration
                $arrCreate['created_at'] = $dataset['updated_at'];
                $arrCreate['updated_at'] = $dataset['updated_at'];
            }

            if ($appId) {
                $arrCreate['application_id'] = $appId;
            }

            return CollectionHasDataset::updateOrCreate(
                $arrCreate,
                [
                    'collection_id' => $collectionId,
                    'dataset_id' => $dataset['id'],
                ]
            );
        } catch (Exception $e) {
            throw new Exception("addCollectionHasDataset :: " . $e->getMessage());
        }
    }

    private function checkInCollectionHasDatasets(int $collectionId, int $datasetId)
    {
        try {
            return CollectionHasDataset::where([
                'collection_id' => $collectionId,
                'dataset_id' => $datasetId,
            ])->first();
        } catch (Exception $e) {
            throw new Exception("checkInCollectionHasDatasets :: " . $e->getMessage());
        }
    }

    private function deleteCollectionHasDatasets(int $collectionId, int $datasetId)
    {
        try {
            return CollectionHasDataset::where([
                'collection_id' => $collectionId,
                'dataset_id' => $datasetId,
            ])->delete();
        } catch (Exception $e) {
            throw new Exception("deleteKeywordDur :: " . $e->getMessage());
        }
    }

    private function checkKeywords(int $collectionId, array $inKeywords)
    {
        $kws = CollectionHasKeyword::where('collection_id', $collectionId)->get();

        foreach($kws as $kw) {
            $kwId = $kw->keyword_id;
            $checkKeyword = Keyword::where('id', $kwId)->first();

            if (!$checkKeyword) {
                $this->deleteCollectionHasKeywords($kwId);
                continue;
            }

            if (in_array($checkKeyword->name, $inKeywords)) continue;

            if (!in_array($checkKeyword->name, $inKeywords)) {
                $this->deleteCollectionHasKeywords($kwId);
            }
        }

        foreach ($inKeywords as $keyword) {
            $keywordId = $this->updateOrCreateKeyword($keyword)->id;
            $this->updateOrCreateDurHasKeywords($collectionId, $keywordId);
        }
    }

    private function updateOrCreateDurHasKeywords(int $collectionId, int $keywordId)
    {
        try {
            return CollectionHasKeyword::updateOrCreate([
                'collection_id' => $collectionId,
                'keyword_id' => $keywordId,
            ]);
        } catch (Exception $e) {
            throw new Exception("addKeywordDur :: " . $e->getMessage());
        }
    }

    private function updateOrCreateKeyword($keyword)
    {
        try {
            return Keyword::updateOrCreate([
                'name' => $keyword,
            ],[
                'name' => $keyword,
                'enabled' => 1,
            ]);
        } catch (Exception $e) {
            throw new Exception("createUpdateKeyword :: " . $e->getMessage());
        }
    } 

    private function deleteCollectionHasKeywords($keywordId)
    {
        try {
            return CollectionHasKeyword::where(['keyword_id' => $keywordId])->delete();
        } catch (Exception $e) {
            throw new Exception("deleteKeywordDur :: " . $e->getMessage());
        }
    }

    private function extractInputDatasetIdToArray(array $inputDatasets): Array
    {
        $response = [];
        foreach ($inputDatasets as $inputDataset) {
            $response[] = $inputDataset['id'];
        }

        return $response;
    }

    /**
     * Insert collection document into elastic index
     *
     * @param integer $collectionId
     * @return void
     */
    private function indexElasticCollections(int $collectionId): void 
    {
        $collection = Collection::with("team")->where('id', $collectionId)->first()->toArray();
        $team = $collection['team'];

        try {
            $toIndex = [
                'publisherName' => isset($team['name']) ? $team['name'] : ''
            ];
            $params = [
                'index' => 'collection',
                'id' => $collectionId,
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
