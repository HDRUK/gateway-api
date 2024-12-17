<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;
use App\Models\Application;
use App\Models\DatasetVersion;
use App\Models\Tool;
use App\Models\Dur;
use App\Models\Publication;
use Illuminate\Support\Str;
use App\Http\Traits\CheckAccess;
use App\Models\CollectionHasDur;
use App\Http\Traits\IndexElastic;
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
use App\Http\Requests\V2\Collection\UpdateCollection;
use App\Models\CollectionHasUser;

class UserCollectionController extends Controller
{
    use IndexElastic;
    use RequestTransformation;
    use CheckAccess;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v2/users/{userId}/collections",
     *    operationId="fetch_user_collections_v2",
     *    tags={"Collections"},
     *    summary="UserCollectionController@indexActive",
     *    description="Returns a list of a users collections",
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
    public function indexActive(Request $request, int $userId): JsonResponse
    {
        try {
            $perPage = $request->has('perPage') ? (int) $request->get('perPage') : Config::get('constants.per_page');

            $sort = $request->query('sort', 'name:desc');
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists(1, $tmp) ? $tmp[1] : 'desc';

            $collections = $this->indexUserCollection(
                $userId,
                'ACTIVE',
                $sort,
                $sortField,
                $sortDirection,
                $perPage
            );

            Auditor::log([
                'action_type' => 'INDEX',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection index user active",
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
     *    path="/api/v2/users/{userId}/collections/status/draft",
     *    operationId="fetch_user_draft_collections_v2",
     *    tags={"Collections"},
     *    summary="UserCollectionController@indexDraft",
     *    description="Returns a list of a users draft collections",
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
    public function indexDraft(Request $request, int $userId): JsonResponse
    {
        try {
            $perPage = $request->has('perPage') ? (int) $request->get('perPage') : Config::get('constants.per_page');

            $sort = $request->query('sort', 'name:desc');
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists(1, $tmp) ? $tmp[1] : 'desc';

            $collections = $this->indexUserCollection(
                $userId,
                'DRAFT',
                $sort,
                $sortField,
                $sortDirection,
                $perPage
            );

            Auditor::log([
                'action_type' => 'INDEX',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection index user draft",
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
     *    path="/api/v2/users/{userId}/collections/status/archived",
     *    operationId="fetch_user_archived_collections_v2",
     *    tags={"Collections"},
     *    summary="UserCollectionController@indexArchived",
     *    description="Returns a list of a users archived collections",
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
    public function indexArchived(Request $request, int $userId): JsonResponse
    {
        try {
            $perPage = $request->has('perPage') ? (int) $request->get('perPage') : Config::get('constants.per_page');

            $sort = $request->query('sort', 'name:desc');
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists(1, $tmp) ? $tmp[1] : 'desc';

            $collections = $this->indexUserCollection(
                $userId,
                'ARCHIVED',
                $sort,
                $sortField,
                $sortDirection,
                $perPage
            );

            Auditor::log([
                'action_type' => 'INDEX',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Collection index user archived",
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
     * @OA\Post(
     *    path="/api/v2/users/collections",
     *    operationId="create_user_collections",
     *    tags={"Collections"},
     *    summary="UserCollectionController@store",
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

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                $collection->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                $collection->update(['updated_at' => $input['updated_at']]);

            }

            // updated_on
            if (array_key_exists('updated_on', $input)) {
                $collection->update(['updated_on' => $input['updated_on']]);
            }

            if ($collection->status === Collection::STATUS_ACTIVE) {
                $this->indexElasticCollections((int) $collection->id);
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
     *    path="/api/v2/users/{userId}/collections/{id}",
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
    public function update(UpdateCollection $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $input['jwt_user'] ?? [];

        if ($jwtUser['id'] != $userId) {
            throw new UnauthorizedException('User does not have permission to use this endpoint to update this collection.');
        }

        // Allow access only to collaborators
        $collHasUsers = CollectionHasUser::where(['collection_id' => $id])->select(['user_id'])->get()->toArray();
        $this->checkAccessCollaborators($input, array_column($collHasUsers, 'user_id'));

        try {
            $initCollection = Collection::withTrashed()->where('id', $id)->first();

            // Don't allow us to edit a team-owned Collection via this endpoint
            if ($initCollection['team_id'] !== null) {
                throw new UnauthorizedException('Cannot update a team-owned Collection via the individual Collection endpoint');
            }

            // if ($initCollection['status'] === Collection::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
            //     throw new Exception('Cannot update current collection! Status already "ARCHIVED"');
            // }

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

            $currentCollection = Collection::where('id', $id)->first();
            if ($currentCollection->status === Collection::STATUS_ACTIVE) {
                $this->indexElasticCollections((int) $id);
            } else {
                $this->deleteCollectionFromElastic((int) $id);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Personal Collection ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getCollectionActiveById($id),
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
     *    path="/api/v2/users/{userId}/collections/{id}",
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
    public function edit(EditCollection $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $input['jwt_user'] ?? [];

        if ($jwtUser['id'] != $userId) {
            throw new UnauthorizedException('User does not have permission to use this endpoint to update this collection.');
        }

        // Allow access only to collaborators
        $collHasUsers = CollectionHasUser::where(['collection_id' => $id])->select(['user_id'])->get()->toArray();
        $this->checkAccessCollaborators($input, array_column($collHasUsers, 'user_id'));

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
                'team_id',
                'status',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            // get initial colleciton
            $initCollection = Collection::where('id', $id)->first();

            // Don't allow us to edit a team-owned Collection via this endpoint
            if ($initCollection['team_id'] !== null) {
                throw new UnauthorizedException('Cannot edit a team-owned Collection via the individual Collection endpoint');
            }

            //update it
            Collection::where('id', $id)->update($array);
            // get updated collection
            $updatedCollection = Collection::where('id', $id)->first();
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
            if ($updatedCollection->status === Collection::STATUS_ACTIVE) {
                $this->indexElasticCollections((int) $id);
            } elseif ($initCollection->status === Collection::STATUS_ACTIVE) {
                $this->deleteCollectionFromElastic((int) $id);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Personal Collection ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getCollectionActiveById($id),
            ], 200);
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
     *    path="/api/v2/users/{userId}/collections/{id}",
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
    public function destroy(DeleteCollection $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $input['jwt_user'] ?? [];

        if ($jwtUser['id'] != $userId) {
            throw new UnauthorizedException('User does not have permission to use this endpoint to update this collection.');
        }

        // Allow access only to collaborators
        $collHasUsers = CollectionHasUser::where(['collection_id' => $id])->select(['user_id'])->get()->toArray();
        $this->checkAccessCollaborators($input, array_column($collHasUsers, 'user_id'));

        try {
            $collection = Collection::where(['id' => $id])->first();

            // Don't allow us to edit a team-owned Collection via this endpoint
            if ($collection['team_id'] !== null) {
                throw new UnauthorizedException('Cannot delete a team-owned Collection via the individual Collection endpoint');
            }
            if ($collection) {
                CollectionHasDatasetVersion::where(['collection_id' => $id])->delete();
                CollectionHasTool::where(['collection_id' => $id])->delete();
                CollectionHasDur::where(['collection_id' => $id])->delete();
                CollectionHasKeyword::where(['collection_id' => $id])->delete();
                CollectionHasPublication::where(['collection_id' => $id])->delete();
                Collection::where(['id' => $id])->delete();

                $this->deleteCollectionFromElastic($id);

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

    private function indexUserCollection(int $userId, string $status, bool $sort, string $sortField, string $sortDirection, int $perPage)
    {
        $collHasUsers = CollectionHasUser::where('user_id', $userId)->select(['collection_id'])->get()->toArray();
        $collections = Collection::whereIn('id', $collHasUsers)
            ->where('status', $status)
            ->with([
                'keywords',
                'tools',
                'dur',
                'publications',
                'team',
                'users',
            ])
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sortField, $sortDirection)
            )
            ->paginate((int) $perPage, ['*'], 'page');

        $collections->getCollection()->transform(function ($collection) {
            if ($collection->image_link && !filter_var($collection->image_link, FILTER_VALIDATE_URL)) {
                $collection->image_link = Config::get('services.media.base_url') .  $collection->image_link;
            }

            return $collection;
        });

        $collections->getCollection()->transform(function ($collection) {
            $collection->setAttribute('datasets', $collection->allDatasets  ?? []);
            return $collection;
        });

        return $collections;
    }

    private function getCollectionActiveById(int $collectionId, bool $trimmed = false)
    {
        $collection = Collection::with([
            'keywords',
            'tools' => function ($query) use ($trimmed) {
                $query->where('tools.status', Tool::STATUS_ACTIVE)
                    ->when($trimmed, function ($q) {
                        $q->select(
                            "tools.id",
                            "tools.name",
                            "tools.created_at",
                            "tools.user_id"
                        );
                    });
            },
            'dur' => function ($query) use ($trimmed) {
                $query->where('dur.status', Dur::STATUS_ACTIVE)
                    ->when($trimmed, function ($q) {
                        $q->select([
                            'dur.id',
                            'dur.project_title',
                            'dur.organisation_name'
                        ]);

                    });
            },
            'publications' => function ($query) use ($trimmed) {
                $query->where('publications.status', Publication::STATUS_ACTIVE)
                    ->when($trimmed, function ($q) {
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
            'team',
        ])
        ->where(['id' => $collectionId])
        ->first();

        if ($collection) {
            if ($collection->image_link && !filter_var($collection->image_link, FILTER_VALIDATE_URL)) {
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

            if ($collection->datasetVersions) {
                $collection->datasetVersions->map(function ($dv) {
                    $dataset = Dataset::where('id', $dv->dataset_id)->first();
                    if ($dataset->status === Dataset::STATUS_ACTIVE) {
                        return $dv;
                    } else {
                        return null;
                    }
                });
            }
        }

        // teams.introduction, comes out with the chars decoded.. collection.description, does not...
        // I debugged it to high hell and got Big L involved and we assume there be dragons...
        // so this is a lil hotfix..
        $collection->description = htmlspecialchars_decode($collection->description);

        return $collection;
    }

    // datasets
    private function checkDatasets(int $collectionId, array $inDatasets, int $userId = null)
    {
        $cols = CollectionHasDatasetVersion::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            $datasetId = DatasetVersion::where('id', $col->dataset_version_id)->select('dataset_id')->get();
            if (count($datasetId) > 0) {
                if (!in_array($datasetId[0]['dataset_id'], $this->extractInputIdToArray($inDatasets))) {
                    $this->deleteCollectionHasDatasetVersions($collectionId, $col->dataset_version_id);
                }
            }
        }

        // LS - This is superflous.
        foreach ($inDatasets as $dataset) {
            $datasetVersionId = Dataset::where('id', (int) $dataset['id'])->first()->latestVersion()->id;
            $checking = $this->checkInCollectionHasDatasetVersions($collectionId, $datasetVersionId);

            if (!$checking) {
                $this->addCollectionHasDatasetVersion($collectionId, $dataset, $datasetVersionId, $userId);
                $this->reindexElastic($dataset['id']);
            } else {
                if ($checking['deleted_at']) {
                    CollectionHasDatasetVersion::withTrashed()->where([
                        'collection_id' => $collectionId,
                        'dataset_version_id' => $datasetVersionId,
                    ])->update(['deleted_at' => null]);
                }
            }
        }
    }

    private function addCollectionHasDatasetVersion(int $collectionId, array $dataset, int $datasetVersionId, int $userId = null)
    {
        try {

            $searchArray = [
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ];

            $arrCreate = [
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
                'deleted_at' => null,
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

            return CollectionHasDatasetVersion::withTrashed()->updateOrCreate($searchArray, $arrCreate);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$arrCreate['user_id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasDatasetVersion :: ' . $e->getMessage());
        }
    }

    private function checkInCollectionHasDatasetVersions(int $collectionId, int $datasetVersionId)
    {
        try {
            return CollectionHasDatasetVersion::withTrashed()->where([
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollectionHasDatasetVersions :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasDatasetVersions(int $collectionId, int $datasetVersionId)
    {
        try {
            return CollectionHasDatasetVersion::where([
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'  .__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasDatasetVersions :: ' . $e->getMessage());
        }
    }

    // tools
    private function checkTools(int $collectionId, array $inTools, int $userId = null)
    {
        $cols = CollectionHasTool::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            if (!in_array($col->tool_id, $this->extractInputIdToArray($inTools))) {
                $this->deleteCollectionHasTools($collectionId, $col->tool_id);
            }
        }

        foreach ($inTools as $tool) {
            $checking = $this->checkInCollectionHasTools($collectionId, (int) $tool['id']);

            if (!$checking) {
                $this->addCollectionHasTool($collectionId, $tool, $userId);
            }
        }
    }

    private function addCollectionHasTool(int $collectionId, array $tool, int $userId = null)
    {
        try {
            $arrCreate = [
                'collection_id' => $collectionId,
                'tool_id' => $tool['id'],
            ];

            if (array_key_exists('user_id', $tool)) {
                $arrCreate['user_id'] = (int) $tool['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $tool)) {
                $arrCreate['reason'] = $tool['reason'];
            }

            if (array_key_exists('updated_at', $tool)) { // special for migration
                $arrCreate['created_at'] = $tool['updated_at'];
                $arrCreate['updated_at'] = $tool['updated_at'];
            }

            return CollectionHasTool::updateOrCreate(
                $arrCreate,
                [
                    'collection_id' => $collectionId,
                    'tool_id' => $tool['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$arrCreate['user_id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasTool :: ' . $e->getMessage());
        }
    }

    private function checkInCollectionHasTools(int $collectionId, int $toolId)
    {
        try {
            return CollectionHasTool::where([
                'collection_id' => $collectionId,
                'tool_id' => $toolId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollectionHasTools :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasTools(int $collectionId, int $toolId)
    {
        try {
            return CollectionHasTool::where([
                'collection_id' => $collectionId,
                'tool_id' => $toolId,
            ])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasTools :: ' . $e->getMessage());
        }
    }

    // durs
    private function checkDurs(int $collectionId, array $inDurs, int $userId = null)
    {
        $cols = CollectionHasDur::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            if (!in_array($col->dur_id, $this->extractInputIdToArray($inDurs))) {
                CollectionHasDur::where([
                    'collection_id' => $collectionId,
                    'dur_id' => $col->dur_id,
                ])->delete();
            }
        }

        foreach ($inDurs as $dur) {
            $checking = CollectionHasDur::where([
                'collection_id' => $collectionId,
                'dur_id' => (int) $dur['id'],
            ])->first();

            if (!$checking) {
                $this->addCollectionHasDur($collectionId, $dur, $userId);
            }
        }
    }

    private function addCollectionHasDur(int $collectionId, array $dur, int $userId = null)
    {
        try {
            $arrCreate = [
                'collection_id' => $collectionId,
                'dur_id' => $dur['id'],
            ];

            if (array_key_exists('user_id', $dur)) {
                $arrCreate['user_id'] = (int)$dur['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $dur)) {
                $arrCreate['reason'] = $dur['reason'];
            }

            if (array_key_exists('updated_at', $dur)) { // special for migration
                $arrCreate['created_at'] = $dur['updated_at'];
                $arrCreate['updated_at'] = $dur['updated_at'];
            }

            return CollectionHasDur::updateOrCreate(
                $arrCreate,
                [
                    'collection_id' => $collectionId,
                    'dur_id' => $dur['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$arrCreate['user_id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasDur :: ' . $e->getMessage());
        }
    }

    // publications
    private function checkPublications(int $collectionId, array $inPublications, int $userId = null)
    {
        $cols = CollectionHasPublication::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            if (!in_array($col->publication_id, $this->extractInputIdToArray($inPublications))) {
                $this->deleteCollectionHasPublications($collectionId, $col->publication_id);
            }
        }

        foreach ($inPublications as $publication) {
            $checking = $this->checkInCollectionHasPublications($collectionId, (int) $publication['id']);

            if (!$checking) {
                $this->addCollectionHasPublication($collectionId, $publication, $userId);
            }
        }
    }

    private function addCollectionHasPublication(int $collectionId, array $publication, int $userId = null)
    {
        try {
            $arrCreate = [
                'collection_id' => $collectionId,
                'publication_id' => $publication['id'],
            ];

            if (array_key_exists('user_id', $publication)) {
                $arrCreate['user_id'] = (int) $publication['user_id'];
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

            return CollectionHasPublication::updateOrCreate(
                $arrCreate,
                [
                    'collection_id' => $collectionId,
                    'publication_id' => $publication['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasPublication :: ' . $e->getMessage());
        }
    }

    private function checkInCollectionHasPublications(int $collectionId, int $publicationId)
    {
        try {
            return CollectionHasPublication::where([
                'collection_id' => $collectionId,
                'publication_id' => $publicationId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollectionHasPublications :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasPublications(int $collectionId, int $publicationId)
    {
        try {
            return CollectionHasPublication::where([
                'collection_id' => $collectionId,
                'publication_id' => $publicationId,
            ])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasPublications :: ' . $e->getMessage());
        }
    }

    // keywords
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

            if (in_array($checkKeyword->name, $inKeywords)) {
                continue;
            }

            if (!in_array($checkKeyword->name, $inKeywords)) {
                $this->deleteCollectionHasKeywords($kwId);
            }
        }

        foreach ($inKeywords as $keyword) {
            $keywordId = $this->updateOrCreateKeyword($keyword)->id;
            $this->updateOrCreateCollectionHasKeywords($collectionId, $keywordId);
        }
    }

    private function updateOrCreateCollectionHasKeywords(int $collectionId, int $keywordId)
    {
        try {
            return CollectionHasKeyword::updateOrCreate([
                'collection_id' => $collectionId,
                'keyword_id' => $keywordId,
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('updateOrCreateCollectionHasKeywords :: ' . $e->getMessage());
        }
    }

    private function updateOrCreateKeyword($keyword)
    {
        try {
            return Keyword::updateOrCreate([
                'name' => $keyword,
            ], [
                'name' => $keyword,
                'enabled' => 1,
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('updateOrCreateKeyword :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasKeywords($keywordId)
    {
        try {
            return CollectionHasKeyword::where(['keyword_id' => $keywordId])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasKeywords :: ' . $e->getMessage());
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

    // add users to collections
    public function createCollectionUsers(int $collectionId, int $creatorId, array $collaboratorIds)
    {
        CollectionHasUser::create([
            'collection_id' => $collectionId,
            'user_id' => $creatorId,
            'role' => 'CREATOR',
        ]);

        $collaboratorIds = array_filter($collaboratorIds, function ($cId) use ($creatorId) {
            return (int)$cId !== (int)$creatorId;
        });

        foreach ($collaboratorIds as $collaboratorId) {
            CollectionHasUser::create([
                'collection_id' => $collectionId,
                'user_id' => $collaboratorId,
                'role' => 'COLLABORATOR',
            ]);
        }
    }

    // update users to collections
    public function updateCollectionUsers(int $collectionId, array $collaboratorIds)
    {
        CollectionHasUser::where([
            'collection_id' => $collectionId,
            'role' => 'COLLABORATOR',
        ])->delete();

        foreach ($collaboratorIds as $collaboratorId) {
            CollectionHasUser::create([
                'collection_id' => $collectionId,
                'user_id' => $collaboratorId,
                'role' => 'COLLABORATOR',
            ]);
        }
    }
}
