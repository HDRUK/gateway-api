<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Http\Traits\CheckAccess;
use App\Models\CollectionHasDur;
use App\Models\CollectionHasTool;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\CollectionHasKeyword;
use App\Exceptions\NotFoundException;
use App\Models\CollectionHasPublication;
use App\Http\Traits\RequestTransformation;
use App\Models\CollectionHasDatasetVersion;
use App\Http\Requests\Collection\GetCollection;
use App\Http\Requests\Collection\DeleteTeamCollection;
use App\Http\Requests\Collection\CreateTeamCollection;
use App\Http\Requests\Collection\UpdateTeamCollection;
use App\Http\Requests\Collection\EditTeamCollection;
use App\Models\CollectionHasUser;

class CollectionController extends Controller
{
    use RequestTransformation;
    use CheckAccess;

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
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="name",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter collections by name"
     *    ),
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter collections by team ID"
     *    ),
     *    @OA\Parameter(
     *       name="user_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter collections by user ID"
     *    ),
     *    @OA\Parameter(
     *       name="title",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter collections by title"
     *    ),
     *    @OA\Parameter(
     *       name="status",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter collections by status (DRAFT, ACTIVE, ARCHIVED)"
     *    ),
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
            $perPage = $request->has('perPage') ? (int) $request->get('perPage') : Config::get('constants.per_page');
            $name = $request->query('name', null);
            $filterTitle = $request->query('title', null);
            $filterStatus = $request->query('status', null);

            $teamId = $request->query('team_id', null);
            $userId = $request->query('user_id', null);

            $sort = $request->query('sort', 'name:desc');
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists(1, $tmp) ? $tmp[1] : 'desc';

            $collections = Collection::when($name, function ($query) use ($name) {
                return $query->where('name', 'LIKE', '%' . $name . '%');
            })
            ->when(
                $filterStatus,
                function ($query) use ($filterStatus) {
                    return $query->where('status', '=', $filterStatus)
                        ->when(
                            $filterStatus === Collection::STATUS_ARCHIVED,
                            function ($query) {
                                return $query->withTrashed();
                            }
                        );
                }
            )
            ->with([
                'keywords',
                'tools',
                'dur',
                'publications',
                // 'userDatasets',
                // 'userTools',
                // 'userPublications',
                'applicationDatasets',
                'applicationTools',
                'applicationPublications',
                'team',
                'users',
            ])
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sortField, $sortDirection)
            )
            ->when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })
            ->when($userId, function ($query) use ($userId, $teamId) {
                $query->where('team_id', '=', $teamId)
                  ->whereHas('users', function ($query) use ($userId) {
                      $query->where('user_id', $userId);
                  });
            })
            ->when($filterTitle, function ($query) use ($filterTitle) {
                return $query->where('name', 'like', '%' . $filterTitle . '%');
            })
            ->paginate((int) $perPage, ['*'], 'page');

            $collections->getCollection()->transform(function ($collection) {
                if ($collection->image_link && !filter_var($collection->image_link, FILTER_VALIDATE_URL)) {
                    $collection->image_link = Config::get('services.media.base_url') .  $collection->image_link;
                }

                return $collection;
            });

            $collections->getCollection()->transform(function ($collection) {
                // $userDatasets = $collection->userDatasets;
                // $userTools = $collection->userTools;
                // $userPublications = $collection->userPublications;
                // $users = $userDatasets->merge($userTools)
                //     ->merge($userPublications)
                //     ->unique('id');
                // $collection->setRelation('users', $users);
                $collection->setAttribute('datasets', $collection->allDatasets  ?? []);

                $applicationDatasets = $collection->applicationDatasets;
                $applicationTools = $collection->applicationTools;
                $applicationPublications = $collection->applicationPublications;
                $applications = $applicationDatasets->merge($applicationTools)
                    ->merge($applicationPublications)
                    ->unique('id');
                $collection->setRelation('applications', $applications);

                // Remove unwanted relations
                unset(
                    // $users,
                    // $userTools,
                    // $userDatasets,
                    // $userPublications,
                    $applications,
                    $applicationTools,
                    $applicationDatasets,
                    $applicationPublications,
                    // $collection->userDatasets,
                    // $collection->userTools,
                    // $collection->userPublications,
                    $collection->applicationDatasets,
                    $collection->applicationTools,
                    $collection->applicationPublications
                );

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
     *    path="/api/v1/collections/count/{field}",
     *    operationId="count_unique_fields_collections",
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
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="user_id",
     *       in="query",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="user id",
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
            $teamId = $request->query('team_id', null);
            $userId = $request->query('user_id', null);
            $counts = Collection::withTrashed()->with(['users'])
                ->when($teamId, function ($query) use ($teamId) {
                    return $query->where('team_id', '=', $teamId);
                })
                ->when($userId, function ($query) use ($userId, $teamId) {
                    $query->where('team_id', '=', $teamId)
                      ->whereHas('users', function ($query) use ($userId) {
                          $query->where('user_id', $userId);
                      });
                })
                ->select($field)
                ->get()
                ->groupBy($field)
                ->map->count();

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

            $collection = $this->getCollectionById($id, $trimmed);

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
     *    path="/api/v1/teams/{teamId}/collections",
     *    operationId="create_team_collections",
     *    tags={"Collections"},
     *    summary="CollectionController@store",
     *    description="Create a new collection for a team",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
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
     *             @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="dur", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
    public function store(CreateTeamCollection $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        // The permissions to add a collection to this team are checked in middleware
        $input['team_id'] = $teamId;

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

            if (array_key_exists('name', $input)) {
                $array['name'] = sanitize_input($input['name']);
            }

            $collection = Collection::create($array);
            $collectionId = (int) $collection->id;

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($collectionId, $datasets, (int)$jwtUser['id']);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($collectionId, $tools, (int)$jwtUser['id']);

            $dur = array_key_exists('dur', $input) ? $input['dur'] : [];
            $this->checkDurs($collectionId, $dur, (int)$jwtUser['id']);

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($collectionId, $publications, (int)$jwtUser['id']);

            $keywords = array_key_exists('keywords', $input) ? $input['keywords'] : [];
            $this->checkKeywords($collectionId, $keywords);

            // add current user as CREATOR
            $this->createCollectionUsers((int)$collectionId, (int)$jwtUser['id'], []);

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

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_team_id' => $teamId,
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Collection ' . $collectionId . ' created',
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
     *    path="/api/v1/teams/{teamId}/collections/{id}",
     *    tags={"Collections"},
     *    summary="Update a collection",
     *    description="Update a collection owned by a team",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
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
    public function update(UpdateTeamCollection $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        $input['team_id'] = $teamId;

        try {
            $initCollection = Collection::withTrashed()->where('id', $id)->first();

            // Check that we have permissions on the currently owning team - the middleware will have checked $teamId from the route
            $owningTeamId = $initCollection->team_id;
            $this->checkAccess($input, $owningTeamId, null, 'team');

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
                'team_id',
                'status',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            Collection::where('id', $id)->update($array);

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($id, $datasets, (int)$jwtUser['id']);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($id, $tools, (int)$jwtUser['id']);

            $dur = array_key_exists('dur', $input) ? $input['dur'] : [];
            $this->checkDurs($id, $dur, (int)$jwtUser['id']);

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($id, $publications, (int)$jwtUser['id']);

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

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_team_id' => array_key_exists('team_id', $array) ? $array['team_id'] : null,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Collection ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getCollectionById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
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
     *    path="/api/v1/teams/{teamId}/collections/{id}",
     *    tags={"Collections"},
     *    summary="Edit a collection",
     *    description="Edit a collection owned by a team",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
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
     *    @OA\Parameter(
     *       name="unarchive",
     *       in="query",
     *       description="Unarchive a collection",
     *       @OA\Schema(
     *          type="string",
     *          description="instruction to unarchive collection",
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
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
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
    public function edit(EditTeamCollection $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        $input['team_id'] = $teamId;

        try {
            if ($request->has('unarchive')) {
                $collection = Collection::withTrashed()
                    ->find($id);
                // Check that we have permissions on the currently owning team - the middleware will have checked $teamId from the route
                $owningTeamId = $collection->team_id;
                $this->checkAccess($input, $owningTeamId, null, 'team');

                if ($request['status'] !== Collection::STATUS_ARCHIVED) {
                    if (in_array($request['status'], [
                        Collection::STATUS_ACTIVE, Collection::STATUS_DRAFT
                    ])) {
                        $collection->status = $request['status'];
                        $collection->deleted_at = null;
                        $collection->save();

                        CollectionHasDatasetVersion::withTrashed()->where('collection_id', $id)->restore();
                        CollectionHasTool::withTrashed()->where('collection_id', $id)->restore();
                        CollectionHasDur::withTrashed()->where('collection_id', $id)->restore();
                        CollectionHasPublication::withTrashed()->where('collection_id', $id)->restore();

                        Auditor::log([
                            'user_id' => (int)$jwtUser['id'],
                            'action_type' => 'UPDATE',
                            'action_name' => class_basename($this) . '@' . __FUNCTION__,
                            'description' => 'Collection ' . $id . ' unarchived and marked as ' . strtoupper($request['status']),
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

                // Handle the 'deleted_at' field based on 'status'
                if (isset($input['status']) && ($input['status'] === Collection::STATUS_ARCHIVED)) {
                    $array['deleted_at'] = Carbon::now();

                } else {
                    $array['deleted_at'] = null;
                }

                // get initial collection
                $initCollection = Collection::withTrashed()->where('id', $id)->first();

                // Check that we have permissions on the currently owning team - the middleware will have checked $teamId from the route
                $owningTeamId = $initCollection->team_id;
                $this->checkAccess($input, $owningTeamId, null, 'team');

                // update it
                Collection::withTrashed()->where('id', $id)->update($array);
                // get updated collection
                $updatedCollection = Collection::withTrashed()->where('id', $id)->first();
                // Check and update related datasets and tools etc if the collection is active

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

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'target_team_id' => $teamId,
                    'action_type' => 'UPDATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Collection ' . $id . ' updated',
                ]);

                return response()->json([
                    'message' => 'success',
                    'data' => $this->getCollectionById($id),
                ], 200);
            }
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
     *    path="/api/v1/teams/{teamId}/collections/{id}",
     *    tags={"Collections"},
     *    summary="Delete a collection",
     *    description="Delete a collection owned by a team",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
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
    public function destroy(DeleteTeamCollection $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $collHasUsers = CollectionHasUser::where(['collection_id' => $id])->select(['user_id'])->get()->toArray();


        try {
            $collection = Collection::where(['id' => $id])->first();
            // Check that we have permissions on the currently owning team - the middleware will have checked $teamId from the route
            $owningTeamId = $collection->team_id;
            $this->checkAccess($input, $owningTeamId, null, 'team');

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
                    'description' => 'Collection ' . $id . ' deleted',
                ]);

                return response()->json([
                    'message' => 'success',
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
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
                        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.datasetType")) as datasetType,
                        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.publisher.name")) as dataCustodian
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
