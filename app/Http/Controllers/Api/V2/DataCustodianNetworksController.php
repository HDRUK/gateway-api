<?php

namespace App\Http\Controllers\Api\V2;

use DB;
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

class DataCustodianNetworksController extends Controller
{
    use IndexElastic;
    use GetValueByPossibleKeys;

    private $networkDatasets = [];
    private $networkDurIds = [];
    private $networkToolIds = [];
    private $networkPublicationIds = [];
    private $networkCollectionIds = [];

    /**
     * @OA\Get(
     *      path="/api/v2/data_custodian_networks",
     *      description="Returns a list of DataCustodianNetworks enabled on the system",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@index",
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

            foreach ($dpc as $data) {
                // Manually rename the field 'data_provider_coll_id' to 'data_custodian_network_id' to provide consistent naming
                // for users of the API. This can be removed if and when internal naming changes too.
                foreach ($data['teams'] as &$team) {
                    $team['pivot']['data_custodian_network_id'] = $team['pivot']['data_provider_coll_id'];
                    unset($team['pivot']['data_provider_coll_id']);
                }
                unset($team);
            }
            unset($data);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataCustodianNetworks list'
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
     *      path="/api/v2/data_custodian_networks/{id}",
     *      description="Return a single DataCustodianNetwork",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataCustodianNetwork ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataCustodianNetwork ID",
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
                'description' => 'DataCustodianNetworks get ' . $id,
            ]);

            $service = array_values(array_filter(explode(",", $dpc->service)));

            $result = array_merge($dpc->toArray(), [
                'service' => empty($service) ? null : $service,
            ]);

            // Manually rename the field 'data_provider_coll_id' to 'data_custodian_network_id' to provide consistent naming
            // for users of the API. This can be removed if and when internal naming changes too.
            foreach ($result['teams'] as &$team) {
                $team['pivot']['data_custodian_network_id'] = $team['pivot']['data_provider_coll_id'];
                unset($team['pivot']['data_provider_coll_id']);
            }
            unset($team);

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
     *      path="/api/v2/data_custodian_networks/{id}/custodians_summary",
     *      description="Return a single DataCustodianNetwork - custodians summary",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@showCustodiansSummary",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataCustodianNetwork ID - summary",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataCustodianNetwork ID - summary",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="teams_counts", type="array", example="{}", @OA\Items()),
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
    public function showCustodiansSummary(Request $request, int $id): JsonResponse
    {
        try {
            $startTime = microtime(true);
            $dpc = DataProviderColl::select('id')
                ->with('teams')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
            ])->first();

            if (!$dpc) {
                return response()->json([
                    'message' => 'DataCustodianNetwork not found or not enabled',
                    'data' => null,
                ], 404);
            }

            $teamsResult = $this->getTeamsCounts(array_pluck($dpc->teams->toArray(), 'id'));

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataCustodianNetwork get ' . $id,
            ]);

            $result = [
                'id' => $dpc->id,
                'teams_counts' => $teamsResult,
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
     * @OA\Get(
     *      path="/api/v2/data_custodian_networks/{id}/entities_summary",
     *      description="Return a single DataCustodianNetwork - summary of entities",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@showSummary",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataCustodianNetwork ID - summary",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataCustodianNetwork ID - summary",
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
    public function showEntitiesSummary(Request $request, int $id): JsonResponse
    {
        try {
            $dpc = DataProviderColl::select('id', 'name', 'img_url', 'enabled', 'url', 'service', 'summary')
                ->with('teams')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
            ])->first();

            if (!$dpc) {
                return response()->json([
                    'message' => 'DataCustodianNetwork not found or not enabled',
                    'data' => null,
                ], 404);
            }

            $teamIds = $this->getTeamsIds($dpc);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataCustodianNetwork get ' . $id,
            ]);

            $ownedDatasets = Dataset::where(['status' => Dataset::STATUS_ACTIVE])
                ->whereIn('team_id', $teamIds)
                ->select([
                    'id', 'user_id', 'team_id'
                ])
                ->get();

            // SC: I've not optimised this into one mega query for metadata on all datasets, because
            // that leads to memory issues. This is fine for now.
            foreach ($ownedDatasets as $dataset) {
                $metadataSummary = $dataset->latestVersion()['metadata']['metadata']['summary'] ?? [];
                $dataset['title'] = $this->getValueByPossibleKeys($metadataSummary, ['title'], '');
                $dataset['populationSize'] = $this->getValueByPossibleKeys($metadataSummary, ['populationSize'], '');
                $dataset['datasetType'] = $this->getValueByPossibleKeys($metadataSummary, ['datasetType'], '');
            }

            // Durs: get all active durs owned by the team and also active durs linked to (all versions of) datasets owned by the team
            $ownedDurs = Dur::where(['status' => Dur::STATUS_ACTIVE])
                ->whereIn('team_id', $teamIds)
                ->select(['id', 'project_title', 'organisation_name', 'status'])
                ->get()
                ->toArray();

            $linkedDursColl = DB::select(
                'SELECT DISTINCT d.id, d.project_title, d.organisation_name, d.status
                FROM datasets ds
                JOIN dataset_versions dv ON dv.dataset_id = ds.id
                JOIN dur_has_dataset_version dhdv ON dv.id = dhdv.dataset_version_id
                JOIN dur d ON dhdv.dur_id = d.id
                WHERE ds.team_id = ? AND d.team_id != ? AND d.status = ? AND ds.status = ?',
                [$id, $id, Dur::STATUS_ACTIVE, Dataset::STATUS_ACTIVE]
            );

            $linkedDurs = array_map(function ($dur) {
                return (array)$dur;
            }, $linkedDursColl);

            $allDurs = [...$ownedDurs, ...$linkedDurs];

            // for each other entity type, get all that are linked to datasets owned by the teams in this DPC
            $linkedToolsColl = DB::select(
                'SELECT DISTINCT t.id, t.name, t.enabled, t.status, t.created_at, t.updated_at
                FROM datasets ds
                JOIN dataset_versions dv ON dv.dataset_id = ds.id
                JOIN dataset_version_has_tool dvht ON dv.id = dvht.dataset_version_id
                JOIN tools t ON dvht.tool_id = t.id
                WHERE ds.team_id IN (' . implode(',', $teamIds) . ') AND t.status = ? AND ds.status = ?',
                [Tool::STATUS_ACTIVE, Dataset::STATUS_ACTIVE]
            );

            $linkedPublicationsColl = DB::select(
                'SELECT DISTINCT p.id, p.paper_title, p.authors, p.publication_type, p.publication_type_mk1, p.status, p.created_at, p.updated_at
                FROM datasets ds
                JOIN dataset_versions dv ON dv.dataset_id = ds.id
                JOIN publication_has_dataset_version phdv ON dv.id = phdv.dataset_version_id
                JOIN publications p ON phdv.publication_id = p.id
                WHERE ds.team_id IN (' . implode(',', $teamIds) . ') AND p.status = ? AND ds.status = ?',
                [Publication::STATUS_ACTIVE, Dataset::STATUS_ACTIVE]
            );

            $linkedCollectionColl = DB::select(
                'SELECT DISTINCT c.id, c.name, c.image_link, c.status, c.created_at, c.updated_at
                FROM datasets ds
                JOIN dataset_versions dv ON dv.dataset_id = ds.id
                JOIN collection_has_dataset_version chdv ON dv.id = chdv.dataset_version_id
                JOIN collections c ON chdv.collection_id = c.id
                WHERE ds.team_id IN (' . implode(',', $teamIds) . ') AND c.status = ? AND ds.status = ?',
                [Collection::STATUS_ACTIVE, Dataset::STATUS_ACTIVE]
            );

            $linkedCollectionColl = array_map(function ($collection) {
                if ($collection->image_link && !preg_match('/^https?:\/\//', $collection->image_link)) {
                    $collection->image_link = Config::get('services.media.base_url') . $collection->image_link;
                }
                return $collection;
            }, $linkedCollectionColl);

            $service = array_values(array_filter(explode(",", $dpc->service)));

            $result = [
                'id' => $dpc->id,
                'datasets_total' => count($ownedDatasets),
                'datasets' => $ownedDatasets,
                'durs_total' => count($allDurs),
                'durs' => $allDurs,
                'tools_total' => count($linkedToolsColl),
                'tools' => $linkedToolsColl,
                'publications_total' => count($linkedPublicationsColl),
                'publications' => $linkedPublicationsColl,
                'collections_total' => count($linkedCollectionColl),
                'collections' => $linkedCollectionColl
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

    public function showInfoSummary(Request $request, int $id): JsonResponse
    {
        try {
            $startTime = microtime(true);
            $dpc = DataProviderColl::select('id', 'name', 'img_url', 'enabled', 'url', 'service', 'summary')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
            ])->first();

            $service = array_values(array_filter(explode(",", $dpc->service)));

            $result = [
                'id' => $dpc->id,
                'name' => $dpc->name,
                'img_url' => (is_null($dpc->img_url) || strlen(trim($dpc->img_url)) === 0) ? '' : (preg_match('/^https?:\/\//', $dpc->img_url) ? $dpc->img_url : Config::get('services.media.base_url') . $dpc->img_url),
                'summary' => $dpc->summary,
                'enabled' => $dpc->enabled,
                'url' => $dpc->url,
                'service' => empty($service) ? null : $service,
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
     *      path="/api/v2/data_custodian_networks",
     *      description="Creates a new DataCustodianNetwork",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataCustodianNetwork definition",
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
     *      path="/api/v2/data_custodian_networks/{id}",
     *      description="Update a DataCustodianNetwork",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataCustodianNetworks ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataCustodianNetwork ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataCustodianNetwork definition",
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
     *      path="/api/v2/data_custodian_networks/{id}",
     *      summary="Edit a DataCustodianNetwork",
     *      description="Edit a DataCustodianNetwork",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataCustodianNetwork ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataCustodianNetwork ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataCustodianNetwork definition",
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
     *      path="/api/v2/data_custodian_networks/{id}",
     *      summary="Delete a DataCustodianNetwork",
     *      description="Delete a DataCustodianNetwork",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataCustodianNetwork ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataCustodianNetwork ID",
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

    public function getTeamsCounts(array $teamIds)
    {
        $teamsResult = [];

        foreach ($teamIds as $teamId) {
            $team = Team::select('id', 'name')->where(['id' => $teamId])->first();
            $counts = $this->getTeamCounts((int) $team->id);

            $teamsResult[] = array_merge([
                'name' => $team->name,
                'id' => $team->id,
            ], $counts);
        }

        return $teamsResult;
    }

    public function getTeamsIDs(DataProviderColl $dp)
    {
        return DataProviderCollHasTeam::where(['data_provider_coll_id' => $dp->id])->pluck('team_id')->toArray();
    }

    public function getTeamCounts(int $teamId)
    {
        $datasetIds = Dataset::where(['team_id' => $teamId])->where('status', 'ACTIVE')->pluck('id')->toArray();

        $teamResourceIds = [
            'durs' => [],
            'publications' => [],
            'tools' => [],
            'collections' => []
        ];
        foreach ($datasetIds as $datasetId) {
            // Note that this call also updates the class variables
            // $networkDatasets, $networkDurIds, $networkPublicationIds, $networkCollectionIds and networkToolIds
            // ahead of them being used in the summary function
            $datasetResources = $this->getDatasetResourceIds($datasetId);
            foreach ($datasetResources as $k => $v) {
                $teamResourceIds[$k] = array_unique(array_merge($v, $teamResourceIds[$k]));
            }
        }

        $ownedDurIds = Dur::where(['team_id' => $teamId])->where('status', 'ACTIVE')->pluck('id')->toArray();
        $ownedPublicationIds = Publication::where(['team_id' => $teamId])->where('status', 'ACTIVE')->pluck('id')->toArray();
        $ownedToolIds = Tool::where(['team_id' => $teamId])->where('status', 'ACTIVE')->pluck('id')->toArray();
        $ownedCollectionIds = Collection::where(['team_id' => $teamId])->where('status', 'ACTIVE')->pluck('id')->toArray();

        // Here we combine counts of those owned by the team and those connected to Datasets owned by the team.
        // This is why the counts on the "Data Custodian" cards on the "Data Custodian Networks" page differ from
        // the counts on the "Data Custodian" landing page, and is a conscious design choice.
        // Users should _not_ expect these to be the same. I believe future FE designs may make this less
        // surprising to users when the two sets of entity links are shown separately.
        $counts = [
            'datasets_count' => count($datasetIds),
            'durs_count' => count(array_unique(array_merge($teamResourceIds['durs'], $ownedDurIds))),
            'publications_count' => count(array_unique(array_merge($teamResourceIds['publications'], $ownedPublicationIds))),
            'tools_count' => count(array_unique(array_merge($teamResourceIds['tools'], $ownedToolIds))),
            'collections_count' => count(array_unique(array_merge($teamResourceIds['collections'], $ownedCollectionIds))),
        ];

        return $counts;
    }

    public function getDatasetResourceIds(int $datasetId)
    {
        $dataset = Dataset::where(['id' => $datasetId])->first();

        $durIds = array_column(DB::select(
            'SELECT DISTINCT d.id
            FROM dataset_versions dv
            JOIN dur_has_dataset_version dhdv ON dv.id = dhdv.dataset_version_id
            JOIN dur d ON dhdv.dur_id = d.id
            WHERE dv.dataset_id = ? AND d.status = ?',
            [$datasetId, Dur::STATUS_ACTIVE]
        ), 'id') ?? [];
        $collectionIds = array_column(DB::select(
            'SELECT DISTINCT c.id
            FROM dataset_versions dv
            JOIN collection_has_dataset_version chdv ON dv.id = chdv.dataset_version_id
            JOIN collections c ON chdv.collection_id = c.id
            WHERE dv.dataset_id = ? AND c.status = ?',
            [$datasetId, Collection::STATUS_ACTIVE]
        ), 'id') ?? [];
        $publicationIds = array_column(DB::select(
            'SELECT DISTINCT p.id
            FROM dataset_versions dv
            JOIN publication_has_dataset_version phdv ON dv.id = phdv.dataset_version_id
            JOIN publications p ON phdv.publication_id = p.id
            WHERE dv.dataset_id = ? AND p.status = ?',
            [$datasetId, Publication::STATUS_ACTIVE]
        ), 'id') ?? [];
        // $toolIds = array_column($dataset->allActiveTools, 'id') ?? [];
        $toolIds = array_column(DB::select(
            'SELECT DISTINCT t.id
            FROM dataset_versions dv
            JOIN dataset_version_has_tool dvht ON dv.id = dvht.dataset_version_id
            JOIN tools t ON dvht.tool_id = t.id
            WHERE dv.dataset_id = ? AND t.status = ?',
            [$datasetId, Tool::STATUS_ACTIVE]
        ), 'id') ?? [];

        $datasetResources = [
            'durs' => $durIds,
            'publications' => $publicationIds,
            'tools' => $toolIds,
            'collections' => $collectionIds
        ];

        return $datasetResources;
    }
}
