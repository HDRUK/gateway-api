<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\License;
use App\Models\DurHasTool;
use App\Models\ToolHasTag;
use App\Models\Application;
use App\Models\TypeCategory;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasTool;
use App\Models\DataProviderColl;
use Illuminate\Http\JsonResponse;
use App\Models\ProgrammingPackage;
use App\Models\PublicationHasTool;
use App\Http\Requests\Tool\GetTool;
use App\Models\ProgrammingLanguage;
use App\Models\ToolHasTypeCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tool\EditTool;
use App\Http\Requests\Tool\CreateTool;
use App\Http\Requests\Tool\DeleteTool;
use App\Http\Requests\Tool\UpdateTool;
use App\Models\DataProviderCollHasTeam;
use MetadataManagementController AS MMC;
use App\Models\ToolHasProgrammingPackage;
use App\Http\Traits\RequestTransformation;
use App\Models\ToolHasProgrammingLanguage;

class ToolController extends Controller
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
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $mongoId = $request->query('mongo_id', null);
            $tools = Tool::with([
                    'user', 
                    'tag',
                    'team',
                    'license',
                    'publications',
                    'durs',
                ])
                ->when($mongoId, function ($query) use ($mongoId) {
                    return $query->where('mongo_id', '=', $mongoId);
                })
                ->where('enabled', 1)
                ->paginate(Config::get('constants.per_page'), ['*'], 'page');


            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool get all",
            ]);

            return response()->json(
                $tools
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
     */
    public function show(GetTool $request, int $id): JsonResponse
    {
        try {
            $tool = $this->getToolById($id);

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool get " . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $tool,
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
     *             @OA\Property( property="license", type="integer", example="1" ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="dataset", type="array", @OA\Items()),
     *             @OA\Property( property="dataset_version", type="array", @OA\Items()),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="associated_authors", type="string", example="string" ),
     *             @OA\Property( property="contact_address", type="string", example="string" ),
     *             @OA\Property( property="publications", type="array", @OA\Items() ),
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
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
                'mongo_object_id', 
                'name', 
                'url', 
                'description', 
                'license', 
                'tech_stack', 
                'category_id', 
                'user_id',
                'enabled',
                'team_id', 
                'mongo_id',
                'associated_authors', 
                'contact_address',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            $tool = Tool::create($array);

            $this->insertToolHasTag($input['tag'], (int) $tool->id);
            if (array_key_exists('dataset', $input)) {
<<<<<<< HEAD
                $datasetVersionIDs = DatasetVersion::whereIn('dataset_id', $input['dataset'])->pluck('id')->all();
                if (!empty($datasetVersionIDs)) {
                    $this->insertDatasetVersionHasTool($datasetVersionIDs, (int) $tool->id);
                }
=======
                $this->insertDatasetVersionHasTool($input['dataset'], (int) $tool->id);
>>>>>>> 9ef749e95f13cb38122378045f9e6bb17fbbd6ca
            }
            if (array_key_exists('programming_language', $input)) {
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int) $tool->id);
            }
            if (array_key_exists('programming_package', $input)) {
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int) $tool->id);
            }
            if (array_key_exists('type_category', $input)) {
                $this->insertToolHasTypeCategory($input['type_category'], (int) $tool->id);
            }

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($tool->id, $publications, $array['user_id'], $appId);

            $this->indexElasticTools((int) $tool->id);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool " . $tool->id . " created",
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $tool->id,
            ], 201);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
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
     *             @OA\Property( property="license", type="integer", example="1" ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="dataset", type="array", @OA\Items()),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="associated_authors", type="string", example="string" ),
     *             @OA\Property( property="contact_address", type="string", example="string" ),
     *             @OA\Property( property="publications", type="array", @OA\Items() ),
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

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
                'mongo_object_id', 
                'name', 
                'url', 
                'description', 
                'license', 
                'tech_stack', 
                'category_id', 
                'user_id',
                'enabled',
                'team_id', 
                'mongo_id',
                'associated_authors', 
                'contact_address',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Tool::withTrashed()->where('id', $id)->update($array);

            ToolHasTag::where('tool_id', $id)->delete();
            $this->insertToolHasTag($input['tag'], (int) $id);

            DatasetVersionHasTool::where('tool_id', $id)->delete();
            if (array_key_exists('dataset', $input)) {
<<<<<<< HEAD
                $datasetVersionIDs = DatasetVersion::whereIn('dataset_id', $input['dataset'])->pluck('id')->all();
                if (!empty($datasetVersionIDs)) {
                    $this->insertDatasetVersionHasTool($datasetVersionIDs, (int) $tool->id);
                }
=======
                $this->insertDatasetVersionHasTool($input['dataset'], (int) $id);
>>>>>>> 9ef749e95f13cb38122378045f9e6bb17fbbd6ca
            }

            if (array_key_exists('programming_language', $input)) {
                ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int) $id);
            }
            if (array_key_exists('programming_package', $input)) {
                ToolHasProgrammingPackage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int) $id);
            }
            if (array_key_exists('type_category', $input)) {
                ToolHasTypeCategory::where('tool_id', $id)->delete();
                $this->insertToolHasTypeCategory($input['type_category'], (int) $id);
            }

            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($id, $publications, $array['user_id'], $appId);

            $this->indexElasticTools((int) $id);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getToolById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/tools/{id}",
     *    operationId="edit_tools",
     *    tags={"Tools"},
     *    summary="ToolController@edit",
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
     *             @OA\Property( property="license", type="integer", example="1" ),
     *             @OA\Property( property="tech_stack", type="string", example="Cumque molestias excepturi quam at." ),
     *             @OA\Property( property="category_id", type="integer", example=1 ),
     *             @OA\Property( property="user_id", type="integer", example=1 ),
     *             @OA\Property( property="team_id", type="integer", example=1 ),
     *             @OA\Property( property="tags", type="array", collectionFormat="multi", @OA\Items( type="integer", format="int64", example=1 ), ),
     *             @OA\Property( property="dataset", type="array", @OA\Items()),
     *             @OA\Property( property="enabled", type="integer", example=1 ),
     *             @OA\Property( property="programming_language", type="array", @OA\Items() ),
     *             @OA\Property( property="programming_package", type="array", @OA\Items() ),
     *             @OA\Property( property="type_category", type="array", @OA\Items() ),
     *             @OA\Property( property="associated_authors", type="string", example="string" ),
     *             @OA\Property( property="contact_address", type="string", example="string" ),
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

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
                'mongo_object_id',
                'name',
                'url',
                'description',
                'license',
                'tech_stack',
                'category_id',
                'user_id',
                'enabled',
                'team_id',
                'mongo_id',
                'associated_authors', 
                'contact_address',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Tool::withTrashed()->where('id', $id)->update($array);

            if (array_key_exists('tag', $input)) {
                ToolHasTag::where('tool_id', $id)->delete();
                $this->insertToolHasTag($input['tag'], (int) $id);
            };

            if (array_key_exists('dataset', $input)) {
                DatasetVersionHasTool::where('tool_id', $id)->delete();
<<<<<<< HEAD
                $datasetVersionIDs = DatasetVersion::whereIn('dataset_id', $input['dataset'])->pluck('id')->all();
                if (!empty($datasetVersionIDs)) {
                    $this->insertDatasetVersionHasTool($datasetVersionIDs, (int) $tool->id);
                }
=======
                $this->insertDatasetVersionHasTool($input['dataset'], (int) $id);
>>>>>>> 9ef749e95f13cb38122378045f9e6bb17fbbd6ca
            }

            if (array_key_exists('programming_language', $input)) {
                ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingLanguage($input['programming_language'], (int) $id);
            }
            if (array_key_exists('programming_package', $input)) {
                ToolHasProgrammingPackage::where('tool_id', $id)->delete();
                $this->insertToolHasProgrammingPackage($input['programming_package'], (int) $id);
            }
            if (array_key_exists('type_category', $input)) {
                ToolHasTypeCategory::where('tool_id', $id)->delete();
                $this->insertToolHasTypeCategory($input['type_category'], (int) $id);
            }

            $userIdFinal = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;
            if (array_key_exists('publications', $input)) {
                $publications = $input['publications'];
                $this->checkPublications($id, $publications, $userIdFinal, $appId);
            }

            $this->indexElasticTools((int) $id);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getToolById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/tools/{id}",
     *    operationId="delete_tools",
     *    tags={"Tools"},
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
     */
    public function destroy(DeleteTool $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            Tool::where('id', $id)->delete();
            ToolHasTag::where('tool_id', $id)->delete();
            DatasetVersionHasTool::where('tool_id', $id)->delete();
            ToolHasProgrammingLanguage::where('tool_id', $id)->delete();
            ToolHasProgrammingPackage::where('tool_id', $id)->delete();
            ToolHasTypeCategory::where('tool_id', $id)->delete();
            DurHasTool::where('tool_id', $id)->delete();
            PublicationHasTool::where('tool_id', $id)->delete();
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Tool " . $id . " deleted",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
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
                if ($value === 0) {
                    continue;
                }
                ToolHasTag::updateOrCreate([
                    'tool_id' => (int) $toolId,
                    'tag_id' => (int) $value,
                ]);
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into DatasetVersionHasTool
     *
     * @param array $dataset
     * @param integer $toolId
     * @return mixed
     */
<<<<<<< HEAD
    private function insertDatasetVersionHasTool(array $dataset, int $toolId): bool
    {
        try {
            $insertData = [];

            foreach ($dataset as $datasetId) {
                $datasetVersionIDs = DatasetVersion::where('dataset_id', $datasetId)->pluck('id')->all();

                if (!empty($datasetVersionIDs)) {
                    foreach ($datasetVersionIDs as $datasetVersionID) {
                        $insertData[] = [
                            'tool_id' => $toolId,
                            'dataset_version_id' => $datasetVersionID,
                        ];
                    }
                } else {
                    // Handle the case where no dataset version IDs were found
                    throw new Exception("No dataset versions found for dataset_id: $datasetId");
                }
            }

            // Perform bulk insert/update
            if (!empty($insertData)) {
                foreach ($insertData as $data) {
                    DatasetVersionHasTool::updateOrCreate($data);
=======
    private function insertDatasetVersionHasTool(array $dataset, int $toolId): mixed
    {
        try {
            foreach ($dataset as $value) {
                $datasetVersionIDs = DatasetVersion::where('dataset_id', $value)->pluck('id')->all();
                if (!empty($datasetVersionIDs)) {
                    foreach ($datasetVersionIDs as $datasetVersionID) {
                        DatasetVersionHasTool::updateOrCreate([
                            'tool_id' => $toolId,
                            'dataset_version_id' => $datasetVersionID,
                        ]);
                    }
                } else {
                    // Handle the case where no dataset version IDs were found if necessary
                    throw new Exception("No dataset versions found for dataset_id: $value");
>>>>>>> 9ef749e95f13cb38122378045f9e6bb17fbbd6ca
                }
            }

            return true;
        } catch (\Throwable $e) {
            // Log the error message if logging is available
            // Log::error($e->getMessage());

            throw new Exception("Error inserting dataset version tools: " . $e->getMessage(), 0, $e);
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
            throw new Exception("addPublicationHasTool :: " . $e->getMessage());
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
            throw new Exception("checkInPublicationHasTools :: " . $e->getMessage());
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
            throw new Exception("deletePublicationHasTools :: " . $e->getMessage());
        }
    }

    private function extractInputIdToArray(array $input): Array
    {
        $response = [];
        foreach ($input as $value) {
            $response[] = $value['id'];
        }

        return $response;
    }
    
    /**
     * Insert tool document into elastic index
     *
     * @param integer $toolId
     * @return void
     */
    private function indexElasticTools(int $toolId): void 
    {
        try {
            $tool = Tool::where('id', $toolId)
                ->with(['programmingLanguages', 'programmingPackages', 'tag', 'category', 'typeCategory', 'license'])
                ->first();

            $license = License::where('id', $tool['license'])->first();

            $typeCategoriesIDs = ToolHasTypeCategory::where('tool_id', $toolId)
                ->pluck('type_category_id')
                ->all();

            $typeCategories = TypeCategory::where('id', $typeCategoriesIDs)
                ->pluck('name')
                ->all();

            $programmingLanguagesIDs = ToolHasProgrammingLanguage::where('tool_id', $toolId)
                ->pluck('programming_language_id')
                ->all();

            $programmingLanguages = ProgrammingLanguage::where('id', $programmingLanguagesIDs)
                ->pluck('name')
                ->all();

            $programmingPackagesIDs = ToolHasProgrammingPackage::where('tool_id', $toolId)
                ->pluck('programming_package_id')
                ->all();

            $programmingPackages = ProgrammingPackage::where('id', $programmingPackagesIDs)
                ->pluck('name')
                ->all();

            $tagIDs = ToolHasTag::where('tool_id', $toolId)
                ->pluck('tag_id')
                ->all();

            $tags = Tag::where('id', $tagIDs)
                ->pluck('description')
                ->all();

<<<<<<< HEAD
            $datasetVersionIDs = DatasetVersionHasTool::where('tool_id', $toolId)
                ->pluck('dataset_version_id')
                ->all();

            $datasetIDs = DatasetVersion::whereIn('dataset_version_id', $datasetVersionIDs)
=======
            $datasetIDs = DatasetVersionHasTool::where('tool_id', $toolId)
>>>>>>> 9ef749e95f13cb38122378045f9e6bb17fbbd6ca
                ->pluck('dataset_id')
                ->all();

            $datasets = Dataset::where('id', $datasetIDs)
                ->with('versions')
                ->get();

            $dataProviderCollId = DataProviderCollHasTeam::where('team_id', $tool['team_id'])
                ->pluck('data_provider_coll_id')
                ->all();

            $dataProviderColl = DataProviderColl::whereIn('id', $dataProviderCollId)
                ->pluck('name')
                ->all();

            $datasetTitles = array();
            foreach ($datasets as $dataset) {
                $metadata = $dataset['versions'][0];
                $datasetTitles[] = $metadata['metadata']['metadata']['summary']['shortTitle'];
            }
            usort($datasetTitles, 'strcasecmp');

            $toIndex = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'license' => $license ? $license['label'] : null,
                'techStack' => $tool['tech_stack'],
                'category' => $tool['category']['name'],
                'typeCategory' => $typeCategories,
                'associatedAuthors' => $tool['associated_authors'],
                'programmingLanguages' => $programmingLanguages,
                'programmingPackages' => $programmingPackages,
                'tags' => $tags,
                'datasetTitles' => $datasetTitles,
                'dataProviderColl' => $dataProviderColl,
            ];

            $params = [
                'index' => 'tool',
                'id' => $toolId,
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
