<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use App\Models\CollectionHasDur;
use App\Http\Traits\IndexElastic;
use App\Models\CollectionHasTool;
use App\Models\CollectionHasUser;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\CollectionHasKeyword;
use App\Exceptions\NotFoundException;
use App\Http\Traits\IntegrationOverride;
use App\Models\CollectionHasPublication;
use App\Http\Traits\RequestTransformation;
use App\Models\CollectionHasDatasetVersion;
use App\Http\Requests\CollectionIntegration\GetCollectionIntegration;
use App\Http\Requests\CollectionIntegration\CreateCollectionIntegration;
use App\Http\Requests\CollectionIntegration\EditCollectionIntegration;
use App\Http\Requests\CollectionIntegration\UpdateCollectionIntegration;
use App\Http\Requests\CollectionIntegration\DeleteCollectionIntegration;

class IntegrationCollectionController extends Controller
{
    use IndexElastic;
    use RequestTransformation;
    use IntegrationOverride;
    use IntegrationOverride;

    /**
     * @OA\Get(
     *    path="/api/v1/integrations/collections",
     *    operationId="fetch_all_collections_integrations",
     *    tags={"Integration Collections"},
     *    summary="IntegrationCollectionController@index",
     *    description="Returns a list of collections",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="name",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter collections by name"
     *    ),
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="per page",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="per page",
     *          ),
     *      ),
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
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $perPage = $request->has('per_page') ? (int) $request->get('per_page') : Config::get('constants.per_page');
            $name = $request->query('name', null);

            $collections = Collection::when($name, function ($query) use ($name) {
                return $query->where('name', 'LIKE', '%' . $name . '%');
            })
            ->with([
                'keywords',
                'tools',
                'dur',
                'publications',
                'userDatasets',
                'userTools',
                'userPublications',
                'applicationDatasets',
                'applicationTools',
                'applicationPublications',
                'team',
            ])->paginate((int) $perPage, ['*'], 'page');

            $collections->getCollection()->transform(function ($collection) {
                $userDatasets = $collection->userDatasets;
                $userTools = $collection->userTools;
                $userPublications = $collection->userPublications;
                $users = $userDatasets->merge($userTools)->merge($userPublications)->unique('id');
                $collection->setRelation('users', $users);

                $applicationDatasets = $collection->applicationDatasets;
                $applicationTools = $collection->applicationTools;
                $applicationPublications = $collection->applicationPublications;
                $applications = $applicationDatasets->merge($applicationTools)->merge($applicationPublications)->unique('id');
                $collection->setRelation('applications', $applications);

                $collection->setAttribute('datasets', $collection->allDatasets  ?? []);
                // Remove unwanted relations
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

                return $collection;
            });

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Collection get all',
            ]);

            return response()->json(
                $collections
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/integrations/collections/{id}",
     *    operationId="fetch_collections_integrations",
     *    tags={"Integration Collections"},
     *    summary="IntegrationCollectionController@show",
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
    public function show(GetCollectionIntegration $request, int $id): JsonResponse
    {
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $collections = $this->getCollectionById($id);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Collection get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $collections,
            ], 200);

            throw new NotFoundException();
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/integrations/collections",
     *    operationId="create_collections_integrations",
     *    tags={"Integration Collections"},
     *    summary="IntegrationCollectionController@store",
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
    public function store(CreateCollectionIntegration $request): JsonResponse
    {
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

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
            } else {
                $userId = (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']);
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

            $array['user_id'] = $userId;
            $collaborators = (array_key_exists('collaborators', $input)) ? $input['collaborators'] : [];
            $this->createCollectionUsers((int)$collectionId, (int)$userId, $collaborators);

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($collectionId, $datasets, $array['user_id'], $appId);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($collectionId, $tools, $array['user_id'], $appId);

            $dur = array_key_exists('dur', $input) ? $input['dur'] : [];
            $this->checkDurs($collectionId, $dur, $array['user_id'], $appId);

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($collectionId, $publications, $array['user_id'], $appId);

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

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Collection ' . $collectionId . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $collectionId,
            ], 201);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/integrations/collections/{id}",
     *    tags={"Integration Collections"},
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
    public function update(UpdateCollectionIntegration $request, int $id): JsonResponse
    {
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

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
            } else {
                $userId = (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']);
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
            $array['user_id'] = $userId;

            $collaborators = (array_key_exists('collaborators', $input)) ? $input['collaborators'] : [];
            $this->updateCollectionUsers((int)$id, $collaborators);

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($id, $datasets, $array['user_id'], $appId);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($id, $tools, $array['user_id'], $appId);

            $dur = array_key_exists('dur', $input) ? $input['dur'] : [];
            $this->checkDurs($id, $dur, $array['user_id'], $appId);

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($id, $publications, $array['user_id'], $appId);

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
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Collection ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getCollectionById($id),
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/integrations/collections/{id}",
     *    tags={"Integration Collections"},
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
    public function edit(EditCollectionIntegration $request, int $id): JsonResponse
    {
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $userId = null;
            $appId = null;
            if ($request->has('userId')) {
                $userId = (int) $input['userId'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int) $input['jwt_user']['id'];
            } elseif (array_key_exists('app_user', $input)) {
                $appId = (int) $input['app']['id'];
            } else {
                $userId = (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']);
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
            $userIdFinal = $userId;

            $collaborators = (array_key_exists('collaborators', $input)) ? $input['collaborators'] : [];
            $this->updateCollectionUsers((int)$id, $collaborators);

            if (array_key_exists('datasets', $input)) {
                $datasets = $input['datasets'];
                $this->checkDatasets($id, $datasets, $userIdFinal, $appId);
            }

            if (array_key_exists('tools', $input)) {
                $tools = $input['tools'];
                $this->checkTools($id, $tools, $userIdFinal, $appId);
            }

            if (array_key_exists('dur', $input)) {
                $dur = $input['dur'];
                $this->checkDurs($id, $dur, $userIdFinal, $appId);
            }

            if (array_key_exists('publications', $input)) {
                $publications = $input['publications'];
                $this->checkPublications($id, $publications, $userIdFinal, $appId);
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
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Collection ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getCollectionById($id),
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/integrations/collections/{id}",
     *    tags={"Integration Collections"},
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
    public function destroy(DeleteCollectionIntegration $request, int $id): JsonResponse
    {
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());
            CollectionHasDatasetVersion::where(['collection_id' => $id])->delete();
            CollectionHasTool::where(['collection_id' => $id])->delete();
            CollectionHasDur::where(['collection_id' => $id])->delete();
            CollectionHasKeyword::where(['collection_id' => $id])->delete();
            CollectionHasPublication::where(['collection_id' => $id])->delete();
            Collection::where(['id' => $id])->delete();

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Collection ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => 'success',
            ], 200);

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // datasets
    private function checkDatasets(int $collectionId, array $inDatasets, ?int $userId = null, ?int $appId = null)
    {
        $cols = CollectionHasDatasetVersion::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            $dataset_id = DatasetVersion::where("id", $col->dataset_version_id)->first()->dataset_id;
            if (!in_array($dataset_id, $this->extractInputIdToArray($inDatasets))) {
                $this->deleteCollectionHasDatasetVersions($collectionId, $col->dataset_version_id);
            }
        }

        foreach ($inDatasets as $dataset) {
            $datasetVersionId = Dataset::where('id', (int) $dataset['id'])->first()->latestVersion()->id;
            $checking = $this->checkInCollectionHasDatasetVersions($collectionId, $datasetVersionId);

            if (!$checking) {
                $this->addCollectionHasDatasetVersion($collectionId, $dataset, $datasetVersionId, $userId, $appId);
                $this->reindexElastic($dataset['id']);
            }
        }
    }

    private function addCollectionHasDatasetVersion(int $collectionId, array $dataset, int $datasetVersionId, ?int $userId = null, ?int $appId = null)
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

            if ($appId) {
                $arrCreate['application_id'] = $appId;
            }

            return CollectionHasDatasetVersion::withTrashed()->updateOrCreate($searchArray, $arrCreate);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasDataset :: ' . $e->getMessage());
        }
    }

    private function checkInCollectionHasDatasetVersions(int $collectionId, int $datasetVersionId)
    {
        try {
            return CollectionHasDatasetVersion::where([
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollectionHasDatasets :: ' . $e->getMessage());
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
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteKeywordDur :: ' . $e->getMessage());
        }
    }

    // tools
    private function checkTools(int $collectionId, array $inTools, ?int $userId = null, ?int $appId = null)
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
                $this->addCollectionHasTool($collectionId, $tool, $userId, $appId);
            }
        }
    }

    private function addCollectionHasTool(int $collectionId, array $tool, ?int $userId = null, ?int $appId = null)
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

            if ($appId) {
                $arrCreate['application_id'] = $appId;
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

            throw new Exception("deleteCollectionHasTools :: " . $e->getMessage());
        }
    }

    // durs
    private function checkDurs(int $collectionId, array $inDurs, ?int $userId = null, ?int $appId = null)
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
                $this->addCollectionHasDur($collectionId, $dur, $userId, $appId);
            }
        }
    }

    private function addCollectionHasDur(int $collectionId, array $dur, ?int $userId = null, ?int $appId = null)
    {
        try {
            $arrCreate = [
                'collection_id' => $collectionId,
                'dur_id' => $dur['id'],
            ];

            if (array_key_exists('user_id', $dur)) {
                $arrCreate['user_id'] = (int) $dur['user_id'];
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

            if ($appId) {
                $arrCreate['application_id'] = $appId;
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
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasDur :: ' . $e->getMessage());
        }
    }

    // publications
    private function checkPublications(int $collectionId, array $inPublications, ?int $userId = null, ?int $appId = null)
    {
        $cols = CollectionHasPublication::where([
            'collection_id' => $collectionId
        ])->get();
        foreach ($cols as $col) {
            if (!in_array($col->publication_id, $this->extractInputIdToArray($inPublications))) {
                $this->deleteCollectionHasPublications($collectionId, $col->publication_id);
            }
        }

        foreach ($inPublications as $publication) {
            $checking = $this->checkInCollectionHasPublications($collectionId, (int) $publication['id']);

            if (!$checking) {
                $this->addCollectionHasPublication($collectionId, $publication, $userId, $appId);
            }
        }
    }

    private function addCollectionHasPublication(int $collectionId, array $publication, ?int $userId = null, ?int $appId = null)
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

            if ($appId) {
                $arrCreate['application_id'] = $appId;
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
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
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
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
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
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception("deleteCollectionHasPublications :: " . $e->getMessage());
        }
    }

    // keywords
    private function checkKeywords(int $collectionId, array $inKeywords)
    {
        $kws = CollectionHasKeyword::where('collection_id', $collectionId)->get();

        foreach ($kws as $kw) {
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
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
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
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('updateOrCreateKeyword :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasKeywords($keywordId)
    {
        try {
            return CollectionHasKeyword::where([
                'keyword_id' => $keywordId
            ])->delete();
        } catch (Exception $e) {
            throw new Exception("deleteCollectionHasKeywords :: " . $e->getMessage());
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

    private function getCollectionById(int $collectionId)
    {
        $collection = Collection::where(['id' => $collectionId])
        ->with([
            'keywords',
            'tools',
            'dur',
            'publications',
            'userDatasets',
            'userTools',
            'userPublications',
            'applicationDatasets',
            'applicationTools',
            'applicationPublications',
            'team',
        ])->first();

        // Set the datasets attribute with the latest datasets
        $collection->setAttribute('datasets', $collection->allDatasets  ?? []);

        $userDatasets = $collection->userDatasets;
        $userTools = $collection->userTools;
        $userPublications = $collection->userPublications;
        $users = $userDatasets->merge($userTools)->merge($userPublications)->unique('id');
        $collection->setRelation('users', $users);

        $applicationDatasets = $collection->applicationDatasets;
        $applicationTools = $collection->applicationTools;
        $applicationPublications = $collection->applicationPublications;
        $applications = $applicationDatasets->merge($applicationTools)->merge($applicationPublications)->unique('id');
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

        return $collection;
    }

    // add users to collections
    public function createCollectionUsers(int $collectionId, int $creatorId, array $collaboratorIds)
    {
        CollectionHasUser::create([
            'collection_id' => $collectionId,
            'user_id' => $creatorId,
            'role' => 'CREATOR',
        ]);

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
