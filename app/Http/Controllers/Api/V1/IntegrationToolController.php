<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Tool;
use App\Models\ToolHasTag;
use App\Models\Application;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasTool;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PublicationHasTool;
use App\Http\Requests\Tool\GetTool;
use App\Models\ToolHasTypeCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tool\EditTool;
use App\Http\Requests\Tool\CreateTool;
use App\Http\Requests\Tool\DeleteTool;
use App\Http\Requests\Tool\UpdateTool;
use App\Http\Traits\IntegrationOverride;
use App\Http\Traits\IndexElastic;
use App\Models\ToolHasProgrammingPackage;
use App\Http\Traits\RequestTransformation;
use App\Models\DurHasTool;
use App\Models\ToolHasProgrammingLanguage;

class IntegrationToolController extends Controller
{
    use IndexElastic;
    use RequestTransformation;
    use IntegrationOverride;

    /**
     * @OA\Get(
     *    path="/api/v1/integrations/tools",
     *    operationId="fetch_all_tools_integrations",
     *    tags={"Tools"},
     *    summary="IntegrationToolController@index",
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
    public function index(Request $request): JsonResponse
    {
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $tools = Tool::with(['user', 'tag', 'team', 'license', 'publications', 'durs'])
                ->where('enabled', 1)
                ->paginate(Config::get('constants.per_page'), ['*'], 'page');

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Integration Tool get all',
            ]);

            return response()->json(
                $tools
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/integrations/tools/{id}",
     *    operationId="fetch_tools_integrations",
     *    tags={"Tools"},
     *    summary="IntegrationToolController@show",
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
    public function show(GetTool $request, int $id): JsonResponse
    {
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $tool = $this->getToolById($id);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Integration Tool get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $tool,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/integrations/tools",
     *    operationId="create_tools_integrations",
     *    tags={"Tools"},
     *    summary="IntegrationToolController@store",
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
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="dataset", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $userId = null;
            $appId = null;
            if (array_key_exists('user_id', $input)) {
                $userId = (int)$input['user_id'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int)$input['jwt_user']['id'];
            } elseif (array_key_exists('app_user', $input)) {
                $appId = (int)$input['app']['id'];
                $app = Application::where(['id' => $appId])->first();
                $userId = (int)$app->user_id;
            }

            $teamId = isset($input['team_id']) ? $input['team_id'] : null;
            $this->overrideBothTeamAndUserId($teamId, $userId, $request->header());

            $tool = Tool::create([
                'mongo_object_id' => array_key_exists('mongo_object_id', $input) ? $input['mongo_object_id'] : null,
                'name' => $input['name'],
                'url' => $input['url'],
                'description' => $input['description'],
                'license' => $input['license'],
                'tech_stack' =>  $input['tech_stack'],
                'category_id' => $input['category_id'],
                'user_id' => $userId,
                'enabled' => $input['enabled'],
                'team_id' => $teamId,
            ]);

            $this->insertToolHasTag($input['tag'], (int) $tool->id);
            if (array_key_exists('dataset', $input)) {
                $this->insertDatasetVersionHasTool($input['dataset'], (int) $tool->id);
            }
            $this->insertToolHasTag($input['tag'], (int) $tool->id);
            if (array_key_exists('programming_language', $input)) {
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int)$tool->id);
            }
            if (array_key_exists('programming_package', $input)) {
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int)$tool->id);
            }
            if (array_key_exists('type_category', $input)) {
                $this->insertToolHasTypeCategory($input['type_category'], (int)$tool->id);
            }

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($tool->id, $publications, $userId, $appId);

            $this->indexElasticTools((int) $tool->id);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Integration Tool ' . $tool->id . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $tool->id,
            ], 201);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/integrations/tools/{id}",
     *    operationId="update_tools_integrations",
     *    tags={"Tools"},
     *    summary="IntegrationToolController@update",
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
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="dataset", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $arrayKeys = [
                'mongo_object_id',
                'name',
                'url',
                'description',
                'license',
                'tech_stack',
                'category_id',
                'enabled',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            $userId = null;
            $appId = null;
            if (array_key_exists('user_id', $input)) {
                $userId = (int)$input['user_id'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int)$input['jwt_user']['id'];
            } elseif (array_key_exists('app_user', $input)) {
                $appId = (int)$input['app']['id'];
                $app = Application::where(['id' => $appId])->first();
                $userId = (int)$app->user_id;
            }
            $teamId = isset($input['team_id']) ? $input['team_id'] : null;
            $this->overrideBothTeamAndUserId($teamId, $userId, $request->header());

            $array['user_id'] = $userId;
            $array['team_id'] = $teamId;

            Tool::withTrashed()->where('id', $id)
                ->where('id', $id)
                ->update($array);

            ToolHasTag::where('tool_id', $id)->delete();
            $this->insertToolHasTag($input['tag'], (int)$id);

            if (array_key_exists('dataset', $input)) {
                DatasetVersionHasTool::where('tool_id', $id)->delete();
                $this->insertDatasetVersionHasTool($input['dataset'], (int) $id);
            }

            if (array_key_exists('programming_language', $input)) {
                ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int)$id);
            }
            if (array_key_exists('programming_package', $input)) {
                ToolHasProgrammingPackage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int)$id);
            }
            if (array_key_exists('type_category', $input)) {
                ToolHasTypeCategory::where('tool_id', $id)->delete();
                $this->insertToolHasTypeCategory($input['type_category'], (int)$id);
            }

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($id, $publications, $userId, $appId);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Integration Tool ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getToolById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/integrations/tools/{id}",
     *    operationId="edit_tools_integrations",
     *    tags={"Tools"},
     *    summary="IntegrationToolController@edit",
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
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="dataset", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $userId = null;
            $appId = null;
            if ($request->has('userId')) {
                $userId = (int)$input['userId'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int)$input['jwt_user']['id'];
            } elseif (array_key_exists('app_user', $input)) {
                $appId = (int)$input['app']['id'];
            }

            $arrayKeys = [
                'mongo_object_id',
                'name',
                'url',
                'description',
                'license',
                'tech_stack',
                'category_id',
                'enabled',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            $teamId = isset($input['team_id']) ? $input['team_id'] : null;
            $this->overrideBothTeamAndUserId($teamId, $userId, $request->header());

            $array['team_id'] = $teamId;

            Tool::withTrashed()->where('id', $id)
                ->where('id', $id)
                ->update($array);
            $userIdFinal = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;

            if (array_key_exists('tag', $input)) {
                ToolHasTag::where('tool_id', $id)->delete();
                $this->insertToolHasTag($input['tag'], (int)$id);
            };

            if (array_key_exists('dataset', $input)) {
                DatasetVersionHasTool::where('tool_id', $id)->delete();
                $this->insertDatasetVersionHasTool($input['dataset'], (int) $id);
            }

            if (array_key_exists('programming_language', $input)) {
                ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int)$id);
            }
            if (array_key_exists('programming_package', $input)) {
                ToolHasProgrammingPackage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int)$id);
            }
            if (array_key_exists('type_category', $input)) {
                ToolHasTypeCategory::where('tool_id', $id)->delete();
                $this->insertToolHasTypeCategory($input['type_category'], (int)$id);
            }

            if (array_key_exists('publications', $input)) {
                $publications = $input['publications'];
                $this->checkPublications($id, $publications, $userIdFinal, $appId);
            }

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Integration Tool ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getToolById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/integrations/tools/{id}",
     *    operationId="delete_tools_integrations",
     *    tags={"Tools"},
     *    summary="IntegrationToolController@destroy",
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
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            // Safe-guard to ensure no one can come along and delete anyone
            // else's uploaded Tool entries.
            $userId = (isset($input['user_id']) ? $input['user_id'] : null);
            $this->overrideUserId($userId, $request->header());

            $tool = Tool::where('id', $id)
                ->delete();

            if ($tool) {
                ToolHasTag::where('tool_id', $id)->delete();
                ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
                ToolHasProgrammingPackage::where('tool_id', $id)->delete();
                ToolHasTypeCategory::where('tool_id', $id)->delete();
                PublicationHasTool::where('tool_id', $id)->delete();
                DurHasTool::where('tool_id', $id)->delete();
            }

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Integration Tool ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function getToolById(int $toolId)
    {
        $tool = Tool::with([
            'user',
            'tag',
            'team',
            'license',
            'programmingLanguages',
            'programmingPackages',
            'typeCategory',
            'publications',
            'durs',
        ])->where([
            'id' => $toolId,
            'enabled' => 1,
        ])->first();

        return $tool;
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
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into DatasetVersionHasTool
     *
     * @param array $datasetIds
     * @param integer $toolId
     * @return mixed
     */
    private function insertDatasetVersionHasTool(array $datasetIds, int $toolId): mixed
    {
        try {
            foreach ($datasetIds as $value) {
                if (is_array($value)) {
                    $datasetVersionIDs = DatasetVersion::where('dataset_id', $value['id'])->pluck('id')->all();

                    foreach ($datasetVersionIDs as $datasetVersionID) {
                        DatasetVersionHasTool::withTrashed()->updateOrCreate([
                            'tool_id' => $toolId,
                            'dataset_version_id' => $datasetVersionID,
                            'link_type' => $value['link_type'],
                            'deleted_at' => null,
                        ]);
                    }
                } else {
                    $datasetVersionIDs = DatasetVersion::where('dataset_id', $value)->pluck('id')->all();

                    foreach ($datasetVersionIDs as $datasetVersionID) {
                        DatasetVersionHasTool::withTrashed()->updateOrCreate([
                            'tool_id' => $toolId,
                            'dataset_version_id' => $datasetVersionID,
                            'deleted_at' => null,
                        ]);
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into ToolHasProgrammingLanguage
     *
     * @param array $programmingLanguages
     * @param integer $toolId
     * @return mixed
     */
    private function insertToolHasProgrammingLanguage(array $programmingLanguages, int $toolId): mixed
    {
        try {
            foreach ($programmingLanguages as $value) {
                ToolHasProgrammingLanguage::updateOrCreate([
                    'tool_id' => (int) $toolId,
                    'programming_language_id' => (int) $value,
                ]);
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into ToolHasProgrammingPackage
     *
     * @param array $programmingPackages
     * @param integer $toolId
     * @return mixed
     */
    private function insertToolHasProgrammingPackage(array $programmingPackages, int $toolId): mixed
    {
        try {
            foreach ($programmingPackages as $value) {
                ToolHasProgrammingPackage::updateOrCreate([
                    'tool_id' => (int) $toolId,
                    'programming_package_id' => (int) $value,
                ]);
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into ToolHasTypeCategory
     *
     * @param array $typeCategories
     * @param integer $toolId
     * @return mixed
     */
    private function insertToolHasTypeCategory(array $typeCategories, int $toolId): mixed
    {
        try {
            foreach ($typeCategories as $value) {
                ToolHasTypeCategory::updateOrCreate([
                    'tool_id' => (int) $toolId,
                    'type_category_id' => (int) $value,
                ]);
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // publications
    private function checkPublications(int $toolId, array $inPublications, int $userId = null, int $appId = null)
    {
        $pubs = PublicationHasTool::where(['tool_id' => $toolId])->get();
        foreach ($pubs as $pub) {
            if (!in_array($pub->publication_id, $this->extractInputIdToArray($inPublications))) {
                $this->deletePublicationHasTools($toolId, $pub->publication_id);
            }
        }

        foreach ($inPublications as $publication) {
            $checking = $this->checkInPublicationHasTools($toolId, (int) $publication['id']);

            if (!$checking) {
                $this->addPublicationHasTool($toolId, $publication, $userId, $appId);
            }
        }
    }

    private function addPublicationHasTool(int $toolId, array $publication, int $userId = null, int $appId = null)
    {
        try {
            $arrCreate = [
                'tool_id' => $toolId,
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

            return PublicationHasTool::updateOrCreate(
                $arrCreate,
                [
                    'tool_id' => $toolId,
                    'publication_id' => $publication['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addPublicationHasTool :: ' . $e->getMessage());
        }
    }

    private function checkInPublicationHasTools(int $toolId, int $publicationId)
    {
        try {
            return PublicationHasTool::where([
                'tool_id' => $toolId,
                'publication_id' => $publicationId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInPublicationHasTools :: ' . $e->getMessage());
        }
    }

    private function deletePublicationHasTools(int $toolId, int $publicationId)
    {
        try {
            return PublicationHasTool::where([
                'tool_id' => $toolId,
                'publication_id' => $publicationId,
            ])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deletePublicationHasTools :: ' . $e->getMessage());
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

}
