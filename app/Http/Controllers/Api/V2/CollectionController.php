<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Collection;
use App\Models\Application;
use App\Models\Dur;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\CollectionsV2Helpers;
use App\Models\CollectionHasDur;
use App\Models\CollectionHasTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Models\CollectionHasKeyword;
use App\Models\CollectionHasPublication;
use App\Exceptions\UnauthorizedException;
use App\Http\Traits\RequestTransformation;
use App\Models\CollectionHasDatasetVersion;
use App\Http\Requests\V2\Collection\CreateCollection;
use App\Http\Requests\V2\Collection\DeleteCollection;
use App\Http\Requests\V2\Collection\EditCollection;
use App\Http\Requests\V2\Collection\GetCollection;
use App\Http\Requests\V2\Collection\UpdateCollection;
use App\Models\CollectionHasUser;

class CollectionController extends Controller
{
    use RequestTransformation;
    use CheckAccess;
    use CollectionsV2Helpers;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v2/collections",
     *    operationId="fetch_all_collections_v2",
     *    tags={"Collections"},
     *    summary="CollectionController@index",
     *    description="Returns a list of collections",
     *    security={{"bearerAuth":{}}},
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
     *                @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="dur", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
            $perPage = request('per_page', Config::get('constants.per_page'));

            $collections = Collection::with([
                'keywords',
                'tools',
                'dur',
                'publications',
                'team',
                'users',
            ])
            ->applySorting()
            ->paginate((int) $perPage, ['*'], 'page');

            $collections->getCollection()->transform(function ($collection) {
                return $this->prependUrl($collection);
            });

            $collections->getCollection()->transform(function ($collection) {
                $collection->setAttribute('datasets', $collection->allDatasets  ?? []);
                return $collection;
            });

            Auditor::log([
                'action_type' => 'INDEX',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection index",
            ]);

            return response()->json(
                $collections
            );
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
     *    path="/api/v2/collections/count/{field}",
     *    operationId="count_unique_fields_collections_v2",
     *    tags={"Collections"},
     *    summary="CollectionController@count",
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
            $counts = Collection::applyCount();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection count",
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
     *    path="/api/v2/collections/{id}",
     *    operationId="fetch_collections_v2",
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
     *    @OA\Parameter(
     *       name="view_type",
     *       in="query",
     *       description="Query flag to show full collection data or a trimmed version (defaults to full).",
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
     *                   @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="dur", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
            $viewType = $request->query('view_type', 'full');
            $trimmed = $viewType === 'mini';

            $collection = $this->getCollectionActiveById($id, $trimmed);

            Auditor::log([
                'action_type' => 'SHOW',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'CohortRequest show ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $collection,
            ], 200);

            throw new NotFoundException();
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
     * @OA\Post(
     *    path="/api/v2/collections",
     *    operationId="create_collections",
     *    tags={"Collections"},
     *    summary="CollectionController@store",
     *    description="Create a new collection owned by an individual",
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
     *             @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="dur", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="collaborators", type="array", example="[]", @OA\Items()),
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
        // no checks on permissions are required, so long as you're logged in, and that will be checked by jwt middleware.

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $arrayKeys = [
                'name',
                'description',
                'image_link',
                'enabled',
                'public',
                'counter',
                'mongo_object_id',
                'mongo_id',
                'status',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            if (array_key_exists('name', $input)) {
                $array['name'] = formatCleanInput($input['name']);
            }
            $collection = Collection::create($array);
            $collectionId = (int) $collection->id;

            $datasets = $input['datasets'] ?? [];

            $this->checkDatasets($collectionId, $datasets, (int)$jwtUser['id']);

            $tools = $input['tools'] ?? [];
            $this->checkTools($collectionId, $tools, (int)$jwtUser['id']);

            $dur = $input['dur'] ?? [];
            $this->checkDurs($collectionId, $dur, (int)$jwtUser['id']);

            $publications = $input['publications'] ?? [];
            $this->checkPublications($collectionId, $publications, (int)$jwtUser['id']);

            $keywords = $input['keywords'] ?? [];
            $this->checkKeywords($collectionId, $keywords);

            // users
            $userId = (int)$jwtUser['id'];
            $collaborators = $input['collaborators'] ?? [];

            $this->createCollectionUsers((int)$collectionId, $userId, $collaborators);

            // updated_on
            if (array_key_exists('updated_on', $input)) {
                $collection->update(['updated_on' => $input['updated_on']]);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Personal Collection ' . $collectionId . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $collectionId,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v2/collections/{id}",
     *    tags={"Collections"},
     *    summary="Update a collection",
     *    description="Update a collection owned by an individual",
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
     *             @OA\Property(property="dur", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="collaborators", type="array", example="[]", @OA\Items()),
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
     *                   @OA\Property(property="dur", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="collaborators", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *             ),
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
        $input = $request->all();
        $jwtUser = $input['jwt_user'] ?? [];

        // Allow access only to collaborators
        $collHasUsers = CollectionHasUser::where(['collection_id' => $id])->select(['user_id'])->get()->toArray();
        $this->checkAccessCollaborators($input, array_column($collHasUsers, 'user_id'));

        try {
            $initCollection = Collection::withTrashed()->where('id', $id)->first();

            // Don't allow us to edit a team-owned Collection via this endpoint
            if ($initCollection['team_id'] !== null) {
                throw new UnauthorizedException('Cannot update a team-owned Collection via the individual Collection endpoint');
            }

            if ($initCollection['status'] === Collection::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current collection! Status already "ARCHIVED"');
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
                'status',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            Collection::where('id', $id)->update($array);

            $datasets = $input['datasets'] ?? [];
            $this->checkDatasets($id, $datasets, (int)$jwtUser['id']);

            $tools = $input['tools'] ?? [];
            $this->checkTools($id, $tools, (int)$jwtUser['id']);

            $dur = $input['dur'] ?? [];
            $this->checkDurs($id, $dur, (int)$jwtUser['id']);

            $publications = $input['publications'] ?? [];
            $this->checkPublications($id, $publications, (int)$jwtUser['id']);

            $keywords = $input['keywords'] ?? [];
            $this->checkKeywords($id, $keywords);

            // users
            $collaborators = $input['collaborators'] ?? [];
            $this->updateCollectionUsers((int)$id, $collaborators);

            // updated_on
            if (array_key_exists('updated_on', $input)) {
                Collection::where('id', $id)->update(['updated_on' => $input['updated_on']]);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Personal Collection ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getCollectionById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v2/collections/{id}",
     *    tags={"Collections"},
     *    summary="Edit a collection",
     *    description="Edit a collection",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="unarchive",
     *       in="query",
     *       description="Unarchive a collection",
     *       @OA\Schema(
     *          type="string",
     *          description="instruction to unarchive collection",
     *       ),
     *    ),
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
     *             @OA\Property(property="dur", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="collaborators", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="public", type="boolean", example="true"),
     *             @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
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
     *                   @OA\Property(property="dur", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="collaborators", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
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
        $input = $request->all();
        $jwtUser = $input['jwt_user'] ?? [];

        // Allow access only to collaborators
        $collHasUsers = CollectionHasUser::where(['collection_id' => $id])->select(['user_id'])->get()->toArray();
        $this->checkAccessCollaborators($input, array_column($collHasUsers, 'user_id'));

        try {
            if ($request->has('unarchive')) {
                $collectionModel = Collection::withTrashed()
                    ->find($id);

                // Don't allow us to edit a team-owned Collection via this endpoint
                if ($collectionModel['team_id'] !== null) {
                    throw new UnauthorizedException('Cannot update a team-owned Collection via the individual Collection endpoint');
                }
                if ($request['status'] !== Collection::STATUS_ARCHIVED) {
                    if (in_array($request['status'], [
                        Collection::STATUS_ACTIVE, Collection::STATUS_DRAFT
                    ])) {
                        $collectionModel->status = $request['status'];
                        $collectionModel->deleted_at = null;
                        $collectionModel->save();

                        CollectionHasDatasetVersion::withTrashed()->where('collection_id', $id)->restore();
                        CollectionHasTool::withTrashed()->where('collection_id', $id)->restore();
                        CollectionHasDur::withTrashed()->where('collection_id', $id)->restore();
                        CollectionHasPublication::withTrashed()->where('collection_id', $id)->restore();

                        Auditor::log([
                            'user_id' => (int)$jwtUser['id'],
                            'action_type' => 'UPDATE',
                            'action_name' => class_basename($this) . '@' . __FUNCTION__,
                            'description' => 'Personal Collection ' . $id . ' unarchived and marked as ' . strtoupper($request['status']),
                        ]);
                    }
                }

                return response()->json([
                    'message' => 'success',
                    'data' => $this->getCollectionById($id),
                ], Config::get('statuscodes.STATUS_OK.code'));
            } else {
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
                    'status',
                ];
                $array = $this->checkEditArray($input, $arrayKeys);

                if (array_key_exists('name', $input)) {
                    $array['name'] = formatCleanInput($input['name']);
                }

                // Handle the 'deleted_at' field based on 'status'
                if (isset($input['status']) && ($input['status'] === Collection::STATUS_ARCHIVED)) {
                    $array['deleted_at'] = Carbon::now();

                } else {
                    $array['deleted_at'] = null;
                }

                // get initial colleciton
                $initCollection = Collection::withTrashed()->where('id', $id)->first();

                // Don't allow us to edit a team-owned Collection via this endpoint
                if ($initCollection['team_id'] !== null) {
                    throw new UnauthorizedException('Cannot edit a team-owned Collection via the individual Collection endpoint');
                }

                //update it
                Collection::withTrashed()->where('id', $id)->update($array);
                // get updated collection
                $updatedCollection = Collection::withTrashed()->where('id', $id)->first();
                // Check and update related datasets and tools etc if the collection is active


                // collaborators
                if (array_key_exists('collaborators', $input)) {
                    $collaborators = (array_key_exists('collaborators', $input)) ? $input['collaborators'] : [];
                    $this->updateCollectionUsers((int)$id, $collaborators);
                }

                if (array_key_exists('datasets', $input)) {
                    $datasets = $input['datasets'];
                    $this->checkDatasets($id, $datasets, (int)$jwtUser['id']);
                }

                if (array_key_exists('tools', $input)) {
                    $tools = $input['tools'];
                    $this->checkTools($id, $tools, (int)$jwtUser['id']);
                }

                if (array_key_exists('dur', $input)) {
                    $dur = $input['dur'];
                    $this->checkDurs($id, $dur, (int)$jwtUser['id']);
                }

                if (array_key_exists('publications', $input)) {
                    $publications = $input['publications'];
                    $this->checkPublications($id, $publications, (int)$jwtUser['id']);
                }

                if (array_key_exists('keywords', $input)) {
                    $keywords = $input['keywords'];
                    $this->checkKeywords($id, $keywords);
                }

                // add in a team
                if (array_key_exists('team_id', $input)) {
                    Collection::where('id', $id)->update(['team_id' => $input['team_id']]);
                }

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'UPDATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Personal Collection ' . $id . ' updated',
                ]);

                return response()->json([
                    'message' => 'success',
                    'data' => $this->getCollectionById($id),
                ], 200);
            }
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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
     *    path="/api/v2/collections/{id}",
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
        $input = $request->all();
        $jwtUser = $input['jwt_user'] ?? [];

        // Allow access only to collaborators
        $collHasUsers = CollectionHasUser::where(['collection_id' => $id])->select(['user_id'])->get()->toArray();
        $this->checkAccessCollaborators($input, array_column($collHasUsers, 'user_id'));

        try {
            $collection = Collection::where(['id' => $id])->first();

            // Don't allow us to edit a team-owned Collection via this endpoint
            if ($collection['team_id'] !== null) {
                throw new UnauthorizedException('Cannot delete a team-owned Collection via the individual Collection endpoint');
            }
            $initialStatus = $collection->status;
            if ($collection) {
                CollectionHasDatasetVersion::where(['collection_id' => $id])->delete();
                CollectionHasTool::where(['collection_id' => $id])->delete();
                CollectionHasDur::where(['collection_id' => $id])->delete();
                CollectionHasKeyword::where(['collection_id' => $id])->delete();
                CollectionHasPublication::where(['collection_id' => $id])->delete();
                Collection::where(['id' => $id])->update(['status' => Collection::STATUS_ARCHIVED]);
                Collection::where(['id' => $id])->delete();

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'DELETE',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => 'Personal Collection ' . $id . ' deleted',
                ]);

                return response()->json([
                    'message' => 'success',
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function getCollectionById(int $collectionId, bool $trimmed = false)
    {
        $collection = Collection::with([
            'keywords',
            'tools' => function ($query) use ($trimmed) {
                $query->when($trimmed, function ($q) {
                    $q->select(
                        "tools.id",
                        "tools.name",
                        "tools.created_at",
                        "tools.user_id"
                    );
                });
            },
            'dur' => function ($query) use ($trimmed) {
                $query->when($trimmed, function ($q) {
                    $q->select([
                        'dur.id',
                        'dur.project_title',
                        'dur.organisation_name'
                    ]);

                });
            },
            'publications' => function ($query) use ($trimmed) {
                $query->when($trimmed, function ($q) {
                    $q->select([
                        "publications.id",
                        "publications.paper_title",
                        "publications.authors",
                        "publications.url",
                        "publications.year_of_publication"
                    ]);
                });
            },
            'users' => function ($query) use ($trimmed) {
                $query->when($trimmed, function ($q) {
                    $q->select([
                        'users.id',
                        'users.name',
                        'users.email',
                     ]);
                });
            },
            'datasetVersions' => function ($query) use ($trimmed) {
                $query->when($trimmed, function ($q) {
                    $q->selectRaw('
                        dataset_versions.id,dataset_versions.dataset_id,
                        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.shortTitle")) as shortTitle,
                        CONVERT(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.populationSize")), SIGNED) as populationSize,
                        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.datasetType")) as datasetType
                    ');
                });
            },
            /*'userDatasets', //not sure what this is, legacy code? commenting out for now - Calum 17/10/24
            'userTools',
            'userPublications',
            'applicationDatasets',
            'applicationTools',
            'applicationPublications',
            */
            'team',
        ])
        ->withTrashed()
        ->where(['id' => $collectionId])
        ->first();

        if ($collection) {
            if ($collection->image_link && !preg_match('/^https?:\/\//', $collection->image_link)) {
                $collection->image_link = Config::get('services.media.base_url') .  $collection->image_link;
            }

            if($collection->users) {
                $collection->users->map(function ($user) {
                    $currentEmail = $user->email;
                    [$username, $domain] = explode('@', $currentEmail);
                    $user->email = Str::mask($username, '*', 1, strlen($username) - 2) . '@' . Str::mask($domain, '*', 1, strlen($domain) - 2);
                    return $user;
                });
            }
        }

        // teams.introduction, comes out with the chars decoded.. collection.description, does not...
        // I debugged it to high hell and got Big L involved and we assume there be dragons...
        // so this is a lil hotfix..
        $collection->description = htmlspecialchars_decode($collection->description);

        //Calum 17/10/2024
        // - commeneting this out
        // - we are only concerned with collection direct linkage
        // - not indirect via publications/users etc.
        // - legacy code, probably can remove but keeping commented out for now
        // Set the datasets attribute with the latest datasets
        /*
        $collection->setAttribute('datasets', $collection->allDatasets  ?? []);

        $userDatasets = $collection->userDatasets;
        $userTools = $collection->userTools;
        $userPublications = $collection->userPublications;
        $users = $userDatasets->merge($userTools)
            ->merge($userPublications)
            ->unique('id');
        $collection->setRelation('users', $users);

        $applicationDatasets = $collection->applicationDatasets;
        $applicationTools = $collection->applicationTools;
        $applicationPublications = $collection->applicationPublications;
        $applications = $applicationDatasets->merge($applicationTools)
            ->merge($applicationPublications)
            ->unique('id');
        $collection->setRelation('applications', $applications);

        unset(
            $users,
            $userTools,
            $userDatasets,
            $userPublications,
            $applications,
            $applicationTools,
            $applicationDatasets,
            $applicationPublications,
            $collection->userDatasets,
            $collection->userTools,
            $collection->userPublications,
            $collection->applicationDatasets,
            $collection->applicationTools,
            $collection->applicationPublications
        );
        */

        return $collection;
    }

}
