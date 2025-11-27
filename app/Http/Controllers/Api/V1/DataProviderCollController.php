<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\Dataset;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use App\Models\DataProviderColl;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\DataProviderCollHasTeam;
use App\Models\Dur;
use App\Models\Publication;
use App\Models\Tool;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Traits\IndexElastic;

class DataProviderCollController extends Controller
{
    use IndexElastic;
    use GetValueByPossibleKeys;

    private $datasets = [];
    private $durs = [];
    private $tools = [];
    private $publications = [];
    private $collections = [];

    /**
     * @OA\Get(
     *      path="/api/v1/data_provider_colls",
     *      summary="List of DataProviderColl's",
     *      description="Returns a list of DataProviderColls enabled on the system",
     *      tags={"DataProviderColl"},
     *      summary="DataProviderColl@index",
     *      security={{"bearerAuth":{}}},
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
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="name", type="string", example="Name"),
     *                      @OA\Property(property="summary", type="string", example="Summary"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                      @OA\Property(property="service", type="string", example="https://example"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();

            $perPage = request('per_page', Config::get('constants.per_page'));
            $mediaBaseUrl = Config::get('services.media.base_url');

            $dpc = DataProviderColl::with([
                'teams'
                ])->where('enabled', 1)
                ->paginate((int) $perPage, ['*'], 'page')
                ->through(function ($item) use ($mediaBaseUrl) {
                    $item->img_url = (is_null($item->img_url) || strlen(trim($item->img_url)) === 0) ? null : (preg_match('/^https?:\/\//', $item->img_url) ? $item->img_url : $mediaBaseUrl . $item->img_url);
                    return $item;
                });

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProviderColl list'
            ]);

            return response()->json(
                $dpc,
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
     *      path="/api/v1/data_provider_colls/{id}",
     *      summary="Return a single DataProviderColl",
     *      description="Return a single DataProviderColl",
     *      tags={"DataProviderColl"},
     *      summary="DataProviderColl@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="summary", type="string", example="Summary"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="service", type="string", example="https://example"),
     *              )
     *          ),
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
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $dpc = DataProviderColl::with([
                'teams',
                ])->where('id', $id)->firstOrFail();

            $mediaBaseUrl = Config::get('services.media.base_url');
            $dpc->img_url = (is_null($dpc->img_url) || strlen(trim($dpc->img_url)) === 0) ? null : (preg_match('/^https?:\/\//', $dpc->img_url) ? $dpc->img_url : $mediaBaseUrl . $dpc->img_url);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProviderColl get ' . $id,
            ]);

            $service = array_values(array_filter(explode(",", $dpc->service)));

            $result = array_merge($dpc->toArray(), [
                'service' => empty($service) ? null : $service,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $result
            ]);
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
     *      path="/api/v1/data_provider_colls/{id}/summary",
     *      description="Return a single DataProviderColl - summary",
     *      tags={"DataProviderColl"},
     *      summary="DataProviderColl@showSummary",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID - summary",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID - summary",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="img_url", type="string", example="http://placeholder"),
     *                  @OA\Property(property="url", type="string", example="http://placeholder.url"),
     *                  @OA\Property(property="summary", type="string", example="Summary"),
     *                  @OA\Property(property="datasets", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="durs", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="tools", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="publications", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="collections", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="service", type="string", example="https://example"),
     *              )
     *          ),
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
    public function showSummary(Request $request, int $id): JsonResponse
    {
        try {
            $dpc = DataProviderColl::select('id', 'name', 'img_url', 'enabled', 'url', 'service')
                ->with('teams')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
            ])->first();

            if (!$dpc) {
                return response()->json([
                    'message' => 'DataProviderColl not found or not enabled',
                    'data' => null,
                ], 404);
            }

            $teamsResult = $this->getTeams($dpc);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProviderColl get ' . $id,
            ]);

            $durs = Dur::select(
                'id',
                'project_title',
                'organisation_name',
                'status',
                'created_at',
                'updated_at'
            )->whereIn('id', $this->durs)->where('status', 'ACTIVE')->get()->toArray();
            $tools = Tool::select(
                'id',
                'name',
                'enabled',
                'status',
                'created_at',
                'updated_at'
            )->with(['user'])->whereIn('id', $this->tools)->where('status', 'ACTIVE')->get()->toArray();
            $publications = Publication::select(
                'id',
                'paper_title',
                'authors',
                'publication_type',
                'publication_type_mk1',
                'status',
                'url',
                'created_at',
                'updated_at'
            )->whereIn('id', $this->publications)->where('status', 'ACTIVE')->get()->toArray();
            $collections = Collection::select(
                'id',
                'name',
                'image_link',
                'status',
                'created_at',
                'updated_at'
            )->whereIn('id', $this->collections)->where('status', 'ACTIVE')->get()->toArray();
            $collections = array_map(function ($collection) {
                if ($collection['image_link'] && !preg_match('/^https?:\/\//', $collection['image_link'])) {
                    $collection['image_link'] = Config::get('services.media.base_url') . $collection['image_link'];
                }
                return $collection;
            }, $collections);

            $service = array_values(array_filter(explode(",", $dpc->service)));

            $result = [
                'id' => $dpc->id,
                'name' => $dpc->name,
                'img_url' => (is_null($dpc->img_url) || strlen(trim($dpc->img_url)) === 0) ? '' : (preg_match('/^https?:\/\//', $dpc->img_url) ? $dpc->img_url : Config::get('services.media.base_url') . $dpc->img_url),
                'summary' => $dpc->summary,
                'enabled' => $dpc->enabled,
                'url' => $dpc->url,
                'service' => empty($service) ? null : $service,
                'teams_counts' => $teamsResult,
                'datasets_total' => count($this->datasets),
                'datasets' => $this->datasets,
                'durs_total' => count($durs),
                'durs' => $durs,
                'tools_total' => count($tools),
                'tools' => $tools,
                'publications_total' => count($publications),
                'publications' => $publications,
                'collections_total' => count($collections),
                'collections' => $collections
            ];

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $result,
            ]);
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
     * @OA\Post(
     *      path="/api/v1/data_provider_colls",
     *      summary="Create a new DataProviderColl",
     *      description="Creates a new DataProviderColl",
     *      tags={"DataProviderColl"},
     *      summary="DataProviderColl@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataProviderColl definition",
     *          @OA\JsonContent(
     *              required={"name", "summary", "enabled", "team_ids"},
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="summary", type="string", example="Summary"),
     *              @OA\Property(property="enabled", type="boolean", example="true"),
     *              @OA\Property(property="service", type="string", example="https://example"),
     *              @OA\Property(property="team_ids", type="array", example="{3, 4, 5}",
     *                  @OA\Items(
     *                      @OA\Property(type="integer")
     *                  )
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example="100")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $array = [
                'enabled' => $input['enabled'],
                'name' => $input['name'],
                'img_url' => $input['img_url'],
                'summary' => $input['summary'],
                'url' => array_key_exists('url', $input) ? $input['url'] : null,
                'service' => array_key_exists('service', $input) ? $input['service'] : null,
            ];
            if (isset($input['url'])) {
                $array['url'] = $input['url'];
            }

            $dpc = DataProviderColl::create($array);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProvider ' . $dpc->id . ' created',
            ]);

            foreach ($input['team_ids'] as $teamId) {
                DataProviderCollHasTeam::create([
                    'data_provider_coll_id' => $dpc->id,
                    'team_id' => $teamId,
                ]);

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataProviderCollHasTeam ' . $dpc->id . '/' . $teamId . ' created',
                ]);
            }

            $this->indexElasticDataProviderColl((int) $dpc->id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $dpc->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
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
     * @OA\Put(
     *      path="/api/v1/data_provider_colls/{id}",
     *      summary="Update a DataProviderColl",
     *      description="Update a DataProviderColl",
     *      tags={"DataProviderColl"},
     *      summary="DataProviderColl@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataProviderColl definition",
     *          @OA\JsonContent(
     *              required={"name", "summary", "enabled", "team_ids"},
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="summary", type="string", example="Summary"),
     *              @OA\Property(property="enabled", type="string", example="true"),
     *              @OA\Property(property="service", type="string", example="https://example"),
     *              @OA\Property(property="team_ids", type="array", example="{3, 4, 5}",
     *                  @OA\Items(
     *                      @OA\Property(type="integer")
     *                  )
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="summary", type="string", example="Summary"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="service", type="string", example="https://example"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $dpc = DataProviderColl::where('id', $id)->first();

            $dpc->enabled = $input['enabled'];
            $dpc->name = $input['name'];
            $dpc->img_url = $input['img_url'];
            $dpc->summary = $input['summary'];
            $dpc->url = (isset($input['url']) ? $input['url'] : $dpc->url);
            $dpc->service = (isset($input['service']) ? $input['service'] : $dpc->url);
            $dpc->save();

            if (isset($input['team_ids']) && !empty($input['team_ids'])) {
                DataProviderCollHasTeam::where('data_provider_coll_id', $dpc->id)->delete();

                foreach ($input['team_ids'] as $teamId) {
                    DataProviderCollHasTeam::create([
                        'data_provider_coll_id' => $dpc->id,
                        'team_id' => $teamId,
                    ]);
                }
            }

            $this->indexElasticDataProviderColl((int) $id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dpc,
            ], Config::get('statuscodes.STATUS_OK.code'));

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
     * @OA\Patch(
     *      path="/api/v1/data_provider_colls/{id}",
     *      summary="Edit a DataProviderColl",
     *      description="Edit a DataProviderColl",
     *      tags={"DataProviderColl"},
     *      summary="DataProviderColl@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataProviderColl definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="summary", type="string", example="Summary"),
     *              @OA\Property(property="enabled", type="string", example="true"),
     *              @OA\Property(property="service", type="string", example="https://example"),
     *              @OA\Property(property="team_ids", type="array", example="{3, 4, 5}",
     *                  @OA\Items(
     *                      @OA\Property(type="integer")
     *                  )
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="summary", type="string", example="Summary"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="service", type="string", example="https://example"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $dpc = DataProviderColl::where('id', $id)->first();

            $dpc->enabled = (isset($input['enabled']) ? $input['enabled'] : $dpc->enabled);
            $dpc->name = (isset($input['name']) ? $input['name'] : $dpc->name);
            $dpc->img_url = (isset($input['img_url']) ? $input['img_url'] : $dpc->img_url);
            $dpc->summary = (isset($input['summary']) ? $input['summary'] : $dpc->summary);
            $dpc->url = (isset($input['url']) ? $input['url'] : $dpc->url);
            $dpc->service = (isset($input['service']) ? $input['service'] : $dpc->service);

            $dpc->save();

            if (isset($input['team_ids']) && !empty($input['team_ids'])) {
                DataProviderCollHasTeam::where('data_provider_coll_id', $dpc->id)->delete();

                foreach ($input['team_ids'] as $teamId) {
                    DataProviderCollHasTeam::create([
                        'data_provider_coll_id' => $dpc->id,
                        'team_id' => $teamId,
                    ]);
                }
            }

            $this->indexElasticDataProviderColl((int) $id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dpc,
            ], Config::get('statuscodes.STATUS_OK.code'));

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
     *      path="/api/v1/data_provider_colls/{id}",
     *      summary="Delete a DataProviderColl",
     *      description="Delete a DataProviderColl",
     *      tags={"DataProviderColl"},
     *      summary="DataProviderColl@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *           ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            DataProviderCollHasTeam::where(['data_provider_coll_id' => $id])->delete();
            DataProviderColl::where(['id' => $id])->delete();

            $this->deleteDataProviderCollFromElastic($id);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProvider ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

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

    public function getTeams(DataProviderColl $dp)
    {
        $idTeams = DataProviderCollHasTeam::where(['data_provider_coll_id' => $dp->id])->pluck('team_id')->toArray();
        $teamsResult = [];

        foreach ($idTeams as $idTeam) {
            $team = Team::select('id', 'name')->where(['id' => $idTeam])->first();
            $counts = $this->getDatasets((int) $team->id);
            $teamCollections = Collection::where(['team_id' => $idTeam])->where('status', 'ACTIVE')->pluck('id')->toArray();

            $this->collections = array_unique([...$this->collections, ...$teamCollections]);

            $teamsResult[] = array_merge([
                'name' => $team->name,
                'id' => $team->id,
            ], $counts);
        }

        return $teamsResult;
    }

    public function getDatasets(int $teamId)
    {
        $datasetIds = Dataset::where(['team_id' => $teamId])->where('status', 'ACTIVE')->pluck('id')->toArray();

        $teamResourceIds = [
            'durs' => [],
            'publications' => [],
            'tools' => [],
            'collections' => []
        ];
        foreach ($datasetIds as $datasetId) {
            $datasetResources = $this->checkingDataset($datasetId);
            foreach ($datasetResources as $k => $v) {
                $teamResourceIds[$k] = array_unique(array_merge($v, $teamResourceIds[$k]));
            }
        }
        $counts = [
            'datasets_count' => count($datasetIds),
            'durs_count' => count($teamResourceIds['durs']),
            'publications_count' => count($teamResourceIds['publications']),
            'tools_count' => count($teamResourceIds['tools']),
            'collections_count' => count($teamResourceIds['collections']),
        ];

        return $counts;
    }

    public function checkingDataset(int $datasetId)
    {
        $dataset = Dataset::where(['id' => $datasetId])->first();

        // Accessed through the accessor
        $durIds = array_column($dataset->allActiveDurs, 'id') ?? [];
        $collectionIds = array_column($dataset->allActiveCollections, 'id') ?? [];
        $publicationIds = array_column($dataset->allActivePublications, 'id') ?? [];
        $toolIds = array_column($dataset->allActiveTools, 'id') ?? [];

        $version = $dataset->latestVersion();
        $withLinks = DatasetVersion::where('id', $version['id'])
            ->with(['linkedDatasetVersions'])
            ->first();

        $dataset->setAttribute('versions', [$withLinks]);

        $metadataSummary = $dataset['versions'][0]['metadata']['metadata']['summary'] ?? [];

        $title = $this->getValueByPossibleKeys($metadataSummary, ['title'], '');
        $populationSize = $this->getValueByPossibleKeys($metadataSummary, ['populationSize'], -1);
        $datasetType = $this->getValueByPossibleKeys($metadataSummary, ['datasetType'], '');

        if (empty($title) || $title === '') {
            Log::error('DataProviderCollController: Dataset title is empty or unknown', [
                'datasetId' => $dataset->id
            ]);
        }

        $this->datasets[] = [
            'id' => $dataset->id,
            'status' => $dataset->status,
            'title' => $title,
            'populationSize' => $populationSize,
            'datasetType' => $datasetType
        ];

        $this->durs = array_unique(array_merge($this->durs, $durIds));
        $this->publications = array_unique(array_merge($this->publications, $publicationIds));
        $this->tools = array_unique(array_merge($this->tools, $toolIds));
        $this->collections = array_unique(array_merge($this->collections, $collectionIds));

        $datasetResources = [
            'durs' => $durIds,
            'publications' => $publicationIds,
            'tools' => $toolIds,
            'collections' => $collectionIds
        ];

        return $datasetResources;
    }
}
