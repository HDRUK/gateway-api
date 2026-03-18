<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Traits\IndexElastic;
use App\Http\Traits\RequestTransformation;
use App\Models\Collection;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Publication;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use Auditor;
use Config;
use DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataCustodianNetworksController extends Controller
{
    use IndexElastic;
    use GetValueByPossibleKeys;
    use RequestTransformation;

    private $networkDatasets = [];
    private $networkDurIds = [];
    private $networkToolIds = [];
    private $networkPublicationIds = [];
    private $networkCollectionIds = [];
    private $associatedDatasetIds = [];

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
     *      path="/api/v2/data_custodian_networks/{id}/datasets_summary",
     *      description="Return a single DataCustodianNetwork - summary of datasets",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@showDatasetsSummary",
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
     *                  @OA\Property(property="datasets", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="datasets_total", type="integer", example=1),
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
    public function showDatasetsSummary(Request $request, int $id): JsonResponse
    {
        try {
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

            $teamIds = $this->getTeamsIds($dpc);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataCustodianNetwork get ' . $id,
            ]);

            $ownedDatasets = Dataset::where(['status' => Dataset::STATUS_ACTIVE])
                ->with(['team:id,name'])
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
                $dataset['team'] = $dataset->team;

            }

            $result = [
                'id' => $dpc->id,
                'datasets_total' => count($ownedDatasets),
                'datasets' => $ownedDatasets,
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
     *                  @OA\Property(property="durs_total", type="integer", example=1),
     *                  @OA\Property(property="durs", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="tools_total", type="integer", example=1),
     *                  @OA\Property(property="tools", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="publications_total", type="integer", example=1),
     *                  @OA\Property(property="publications", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="collections_total", type="integer", example=1),
     *                  @OA\Property(property="collections", type="array", example="{}", @OA\Items()),
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

            $teamIds = $this->getTeamsIds($dpc);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataCustodianNetwork get ' . $id,
            ]);

            // Collections: get all active collections owned/associated linked with datasets/durs/tools/publications
            $collectionHasDatasets = $this->linkCollectionsWithDatasetsByTeamIds($teamIds);
            $collectionHasDurs = $this->linkCollectionsWithDursByTeamIds($teamIds);
            $associatedDurs = $this->associatedDurs($teamIds, $collectionHasDurs['dur_ids']);
            $collectionHasTools = $this->linkCollectionsWithToolsByTeamIds($teamIds);
            $associatedTools = $this->associatedTools($teamIds, $collectionHasTools['tool_ids']);
            $collectionHasPublications = $this->linkCollectionsWithPublicationsByTeamIds($teamIds);
            $associatedPublications = $this->associatedPublications($teamIds, $collectionHasPublications['publication_ids']);

            // Durs: get all active durs owned/associated linked with datasets
            $durs = $this->linkDursByTeamIds($teamIds);

            // Tools: get all active tools owned/associated linked with datasets
            $tools = $this->linkToolsByTeamIds($teamIds);

            // Publications: get all active publications owned/associated linked with datasets
            $publications = $this->linkPublicationsByTeamIds($teamIds);

            // associated Datasets
            $this->associatedDatasetIds = array_values(array_unique($this->associatedDatasetIds));

            $ownedCollections = $collectionHasDatasets['owned'];
            $totalOwnedCollections = count($ownedCollections);
            $associatedCollections = collect(array_merge($collectionHasDatasets['associated'], $collectionHasDurs['associated'], $collectionHasTools['associated'], $collectionHasPublications['associated']))->unique('id')->values()->all();
            $totalAssociatedCollections = count($associatedCollections);

            $ownedDurs = $durs['owned'];
            $totalOwnedDurs = count($ownedDurs);
            $associatedDurs = collect(array_merge($associatedDurs, $durs['associated']))->unique('id')->values()->all();
            $totalAssociatedDurs = count($associatedDurs);

            $ownedTools = $tools['owned'];
            $totalOwnedTools = count($ownedTools);
            $associatedTools = collect(array_merge($associatedTools, $tools['associated']))->unique('id')->values()->all();
            $totalAssociatedTools = count($associatedTools ?? []);

            $ownedPublications = $publications['owned'];
            $totalOwnedPublications = count($ownedPublications);
            $associatedPublications = collect(array_merge($associatedPublications, $publications['associated']))->unique('id')->values()->all();
            $totalAssociatedPublications = count($associatedPublications ?? []);

            $associatedDatasets = $this->associatedDatasets($this->associatedDatasetIds ?? []);
            $totalAssociatedDatasets = count($associatedDatasets);


            $result = [
                'id' => $dpc->id,
                // 'teamIds' => implode(',', $teamIds),

                'collections_total' => $totalOwnedCollections,
                'associated_collections_total' => $totalAssociatedCollections,
                'durs_total' => $totalOwnedDurs,
                'associated_durs_total' => $totalAssociatedDurs,
                'tools_total' => $totalOwnedTools,
                'associated_tools_total' => $totalAssociatedTools,
                'publications_total' => $totalOwnedPublications,
                'associated_publications_total' => $totalAssociatedPublications,
                'associated_datasets_total' => $totalAssociatedDatasets,

                'collections' => $ownedCollections,
                'associated_collections' => $associatedCollections,
                'durs' => $ownedDurs,
                'associated_durs' => $associatedDurs,
                'tools' => $ownedTools,
                'associated_tools' => $associatedTools,
                'publications' => $ownedPublications,
                'associated_publications' => $associatedPublications,
                'associated_datasets' => $associatedDatasets,
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

    private function linkCollectionsWithDatasetsByTeamIds(array $teamIds)
    {
        $strTeamIds = implode(',', $teamIds);

        $linkCollections = DB::select(
            'SELECT c.id, c.name, c.image_link, c.team_id, ds.id ds_id, ds.team_id ds_team_id, t.id t_id, t.name t_name
            FROM collections c
            LEFT JOIN teams t ON t.id = c.team_id
            LEFT JOIN collection_has_dataset_version chdv ON chdv.collection_id = c.id
            LEFT JOIN dataset_versions dv ON dv.id = chdv.dataset_version_id
            LEFT JOIN datasets ds ON ds.id = dv.dataset_id
            WHERE c.status = ? 
            AND (
                c.team_id IN (' . $strTeamIds . ')
                OR (ds.team_id IN (' . $strTeamIds . ') AND ds.status = ?)
            )',
            [Collection::STATUS_ACTIVE,Dataset::STATUS_ACTIVE]
        );

        if (empty($linkCollections)) {
            return [
                'owned' => [],
                'associated' => [],
            ];
        }

        $this->associatedDatasetIds = collect($linkCollections)
            ->filter(fn ($row) => !in_array((int) $row->ds_team_id, $teamIds))
            ->pluck('ds_id')
            ->unique()
            ->values()
            ->all();

        $mapped = collect($linkCollections)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'         => $group->first()->id,
                'name'       => $group->first()->name,
                'image_link' => ($group->first()->image_link && !preg_match('/^https?:\/\//', $group->first()->image_link))
                    ? Config::get('services.media.base_url') . $group->first()->image_link
                    : $group->first()->image_link,
                'relation'   => in_array($group->first()->team_id, $teamIds) ? 'owned' : 'associated',
                'team'       => $group->first()->t_id ? [
                    'id'   => $group->first()->t_id,
                    'name' => $group->first()->t_name,
                ] : null,
            ]);

        return [
            'owned'      => $mapped->where('relation', 'owned')->values()->all(),
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
        ];
    }

    private function linkCollectionsWithDursByTeamIds(array $teamIds)
    {
        $strTeamIds = implode(',', $teamIds);

        $linkCollections = DB::select(
            'SELECT c.id, c.name, c.image_link, c.team_id, dur.id dur_id, t.id t_id, t.name t_name
            FROM collections c
            LEFT JOIN teams t ON t.id = c.team_id
            LEFT JOIN collection_has_durs chd ON chd.collection_id = c.id
            LEFT JOIN dur ON dur.id = chd.dur_id
            WHERE c.status = ?
            AND (
                c.team_id IN (' . $strTeamIds . ')
                OR (dur.team_id IN (' . $strTeamIds . ') AND dur.status = ?)
            )',
            [Collection::STATUS_ACTIVE, Dur::STATUS_ACTIVE]
        );

        if (empty($linkCollections)) {
            return [
                'owned' => [],
                'associated' => [],
            ];
        }

        $durIds = collect($linkCollections)->pluck('d_id')->filter()->unique()->values()->toArray();

        $mapped = collect($linkCollections)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'          => $group->first()->id,
                'name'        => $group->first()->name,
                'image_link'  => ($group->first()->image_link && !preg_match('/^https?:\/\//', $group->first()->image_link))
                    ? Config::get('services.media.base_url') . $group->first()->image_link
                    : $group->first()->image_link,
                'relation'    => in_array($group->first()->team_id, $teamIds) ? 'owned' : 'associated',
                'team'        => $group->first()->t_id ? [
                    'id'   => $group->first()->t_id,
                    'name' => $group->first()->t_name,
                ] : null,
            ]);

        return [
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
            'dur_ids'    => $durIds,
        ];
    }

    private function associatedDurs(array $teamIds, array $durIds)
    {
        if (!$durIds || count($durIds) === 0) {
            return [];
        }

        $strTeamIds = implode(',', $teamIds);

        $linkDurs = DB::select(
            'SELECT dur.id, dur.project_title, dur.organisation_name, dur.status, dur.team_id, t.id as t_id, t.name as t_name
            FROM dur
            LEFT JOIN teams t ON t.id = dur.team_id
            WHERE dur.status = ? AND dur.team_id NOT IN (' . $strTeamIds . ') AND dur.id IN (' . implode(',', $durIds) . ')',
            [Dur::STATUS_ACTIVE]
        );

        if (empty($linkDurs)) {
            return [];
        }

        return collect($linkDurs)
            ->map(fn ($item) => [
                'id'                => $item->id,
                'project_title'     => $item->project_title,
                'organisation_name' => $item->organisation_name,
                'relation'          => 'associated',
                'team'              => $item->t_id ? [
                    'id'   => $item->t_id,
                    'name' => $item->t_name,
                ] : null,
            ])
            ->values()
            ->all();
    }

    private function linkCollectionsWithToolsByTeamIds(array $teamIds)
    {
        $strTeamIds = implode(',', $teamIds);

        $linkCollections = DB::select(
            'SELECT c.id, c.name, c.image_link, c.public, c.team_id, t.id as t_id, tt.id tt_id, tt.name tt_name
            FROM collections c
            LEFT JOIN teams tt ON tt.id = c.team_id
            LEFT JOIN collection_has_tools cht ON cht.collection_id = c.id
            LEFT JOIN tools t ON t.id = cht.tool_id
            WHERE c.status = ?
            AND (
                c.team_id IN (' . $strTeamIds . ')
                OR (t.team_id IN (' . $strTeamIds . ') AND t.status = ?)
            )',
            [Collection::STATUS_ACTIVE, Tool::STATUS_ACTIVE]
        );

        if (empty($linkCollections)) {
            return [
                'owned' => [],
                'associated' => [],
            ];
        }

        $toolIds = collect($linkCollections)->pluck('t_id')->filter()->unique()->values()->toArray();

        $mapped = collect($linkCollections)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'          => $group->first()->id,
                'name'        => $group->first()->name,
                'image_link'  => ($group->first()->image_link && !preg_match('/^https?:\/\//', $group->first()->image_link))
                    ? Config::get('services.media.base_url') . $group->first()->image_link
                    : $group->first()->image_link,
                'relation'    => in_array($group->first()->team_id, $teamIds) ? 'owned' : 'associated',
                'team'        => $group->first()->tt_id ? [
                    'id'   => $group->first()->tt_id,
                    'name' => $group->first()->tt_name,
                ] : null,
            ]);

        return [
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
            'tool_ids'   => $toolIds,
        ];
    }

    public function associatedTools(array $teamIds, array $toolIds)
    {
        if (!$toolIds || count($toolIds) === 0) {
            return [];
        }

        $strTeamIds = implode(',', $teamIds);
        $strToolIds = implode(',', $toolIds);

        $linkTools = DB::select(
            'SELECT t.id, t.name, t.user_id, t.team_id, tt.id tt_id, tt.name tt_name
            FROM tools t
            LEFT JOIN teams tt ON tt.id = t.team_id
            WHERE t.status = ?
                AND t.team_id NOT IN (' . $strTeamIds . ')
                AND t.id IN (' . $strToolIds . ')',
            [Tool::STATUS_ACTIVE]
        );

        if (empty($linkTools)) {
            return [];
        }

        $userIds = array_unique(array_column($linkTools, 'user_id'));

        $users = User::whereIn('id', $userIds)
            ->select(
                'id',
                DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE firstname END as firstname"),
                DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE lastname END as lastname"),
                'is_admin'
            )
            ->get()
            ->keyBy('id');

        return collect($linkTools)
            ->map(function ($tool) use ($users) {
                $user = isset($users[$tool->user_id])
                    ? array_intersect_key($users[$tool->user_id]->toArray(), array_flip(['firstname', 'lastname']))
                    : array_fill_keys(['firstname', 'lastname'], null);

                return [
                    'id'       => $tool->id,
                    'name'     => $tool->name,
                    'user'     => $user,
                    'relation' => 'associated',
                    'team'     => $tool->tt_id ? [
                        'id'   => $tool->tt_id,
                        'name' => $tool->tt_name,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    private function linkCollectionsWithPublicationsByTeamIds(array $teamIds)
    {
        $strTeamIds = implode(',', $teamIds);

        $linkCollections = DB::select(
            'SELECT c.id, c.name, c.image_link, c.team_id, p.id as p_id, tt.id as tt_id, tt.name as tt_name
            FROM collections c
            LEFT JOIN teams tt ON tt.id = c.team_id
            LEFT JOIN collection_has_publications chp ON chp.collection_id = c.id
            LEFT JOIN publications p ON p.id = chp.publication_id
            WHERE c.status = ?
            AND (
                c.team_id IN (' . $strTeamIds . ')
                OR (p.team_id IN (' . $strTeamIds . ') AND p.status = ?)
            )',
            [Collection::STATUS_ACTIVE, Publication::STATUS_ACTIVE]
        );

        if (empty($linkCollections)) {
            return [
                'owned' => [],
                'associated' => [],
            ];
        }

        $publicationIds = collect($linkCollections)->pluck('p_id')->filter()->unique()->values()->toArray();

        $mapped = collect($linkCollections)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'          => $group->first()->id,
                'name'        => $group->first()->name,
                'image_link'  => ($group->first()->image_link && !preg_match('/^https?:\/\//', $group->first()->image_link))
                    ? Config::get('services.media.base_url') . $group->first()->image_link
                    : $group->first()->image_link,
                'relation'    => in_array($group->first()->team_id, $teamIds) ? 'owned' : 'associated',
                'team'        => $group->first()->tt_id ? [
                    'id'   => $group->first()->tt_id,
                    'name' => $group->first()->tt_name,
                ] : null,
            ]);

        return [
            'associated'      => $mapped->where('relation', 'associated')->values()->all(),
            'publication_ids' => $publicationIds,
        ];
    }

    private function associatedPublications(array $teamIds, array $publicationIds)
    {
        if (!$publicationIds || count($publicationIds) === 0) {
            return [];
        }

        $strTeamIds = implode(',', $teamIds);
        $strPublicationIds = implode(',', $publicationIds);

        $linkPublications = DB::select(
            'SELECT p.id, p.paper_title, p.authors, p.url, p.team_id, tt.id as tt_id, tt.name as tt_name
            FROM publications p
            LEFT JOIN teams tt ON tt.id = p.team_id
            WHERE p.status = ? 
              AND p.team_id NOT IN (' . $strTeamIds . ') 
              AND p.id IN (' . $strPublicationIds . ')',
            [Publication::STATUS_ACTIVE]
        );

        if (empty($linkPublications)) {
            return [];
        }

        return collect($linkPublications)
            ->map(fn ($item) => [
                'id'          => $item->id,
                'paper_title' => $item->paper_title,
                'authors'     => $item->authors,
                'url'         => $item->url,
                'relation'    => 'associated',
                'team'        => $item->tt_id ? [
                    'id'   => $item->tt_id,
                    'name' => $item->tt_name,
                ] : null,
            ])
            ->values()
            ->all();
    }

    public function linkDursByTeamIds(array $teamIds)
    {
        $strTeamIds = implode(',', $teamIds);

        $linkDurs = DB::select(
            'SELECT dur.id, dur.project_title, dur.organisation_name, dur.status, dur.team_id,
                    t.id as t_id, t.name as t_name,
                    ds.id as ds_id, ds.team_id as ds_team_id
            FROM dur
            LEFT JOIN teams t ON t.id = dur.team_id
            LEFT JOIN dur_has_dataset_version dhdv ON dhdv.dur_id = dur.id
            LEFT JOIN dataset_versions dv ON dv.id = dhdv.dataset_version_id
            LEFT JOIN datasets ds ON ds.id = dv.dataset_id
            WHERE dur.status = ?
            AND (
                dur.team_id IN (' . $strTeamIds . ')
                OR (ds.team_id IN (' . $strTeamIds . ') AND ds.status = ?)
            )',
            [Dur::STATUS_ACTIVE, Dataset::STATUS_ACTIVE]
        );

        if (empty($linkDurs)) {
            return [
                'owned' => [],
                'associated' => [],
            ];
        }

        $this->associatedDatasetIds = collect($linkDurs)
            ->filter(fn ($row) => !in_array((int) $row->ds_team_id, $teamIds))
            ->pluck('ds_id')
            ->unique()
            ->values()
            ->all();

        $mapped = collect($linkDurs)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'                => $group->first()->id,
                'project_title'     => $group->first()->project_title,
                'organisation_name' => $group->first()->organisation_name,
                'relation'          => in_array($group->first()->team_id, $teamIds) ? 'owned' : 'associated',
                'team'              => $group->first()->t_id ? [
                    'id'   => $group->first()->t_id,
                    'name' => $group->first()->t_name,
                ] : null,
            ]);

        return [
            'owned'      => $mapped->where('relation', 'owned')->values()->all(),
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
        ];
    }

    private function linkToolsByTeamIds(array $teamIds)
    {
        $strTeamIds = implode(',', $teamIds);

        $linkTools = DB::select(
            'SELECT t.id, t.name, t.user_id, t.team_id, ds.id as ds_id, ds.team_id as ds_team_id, tt.id as tt_id, tt.name as tt_name
            FROM tools t
            LEFT JOIN teams tt ON tt.id = t.team_id
            LEFT JOIN dataset_version_has_tool dvht ON dvht.tool_id = t.id
            LEFT JOIN dataset_versions dv ON dv.id = dvht.dataset_version_id
            LEFT JOIN datasets ds ON ds.id = dv.dataset_id
            WHERE t.status = ?
            AND (
                t.team_id IN (' . $strTeamIds . ')
                OR (ds.team_id IN (' . $strTeamIds . ') AND ds.status = ?)
            )',
            [Tool::STATUS_ACTIVE, Dataset::STATUS_ACTIVE]
        );

        if (empty($linkTools)) {
            return [
                'owned' => [],
                'associated' => [],
            ];
        }

        $this->associatedDatasetIds = collect($linkTools)
            ->filter(fn ($row) => !in_array((int) $row->ds_team_id, $teamIds))
            ->pluck('ds_id')
            ->unique()
            ->values()
            ->all();

        $userIds = array_unique(array_column($linkTools, 'user_id'));

        $users = User::whereIn('id', $userIds)
            ->select(
                'id',
                DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE firstname END as firstname"),
                DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE lastname END as lastname"),
                'is_admin'
            )
            ->get()
            ->keyBy('id');

        $mapped = collect($linkTools)
            ->groupBy('id')
            ->map(function ($group) use ($users, $teamIds) {
                $tool = $group->first();

                $user = isset($users[$tool->user_id])
                    ? array_intersect_key($users[$tool->user_id]->toArray(), array_flip(['firstname', 'lastname']))
                    : array_fill_keys(['firstname', 'lastname'], null);

                return [
                    'id'       => $tool->id,
                    'name'     => $tool->name,
                    'user'     => $user,
                    'relation' => in_array($tool->team_id, $teamIds) ? 'owned' : 'associated',
                    'team'     => $tool->tt_id ? [
                        'id'   => $tool->tt_id,
                        'name' => $tool->tt_name,
                    ] : null,
                ];
            });

        return [
            'owned'      => $mapped->where('relation', 'owned')->values()->all(),
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
        ];
    }

    private function linkPublicationsByTeamIds(array $teamIds)
    {
        $strTeamIds = implode(',', $teamIds);

        $linkPublications = DB::select(
            'SELECT p.id, p.paper_title, p.authors, p.url, p.team_id as p_team_id, ds.id as ds_id, ds.team_id as ds_team_id, phdv.link_type as phdv_link_type, t.id as t_id, t.name as t_name
            FROM publications p
            LEFT JOIN teams t ON t.id = p.team_id
            LEFT JOIN publication_has_dataset_version phdv ON phdv.publication_id = p.id
            LEFT JOIN dataset_versions dv ON dv.id = phdv.dataset_version_id
            LEFT JOIN datasets ds ON ds.id = dv.dataset_id
            WHERE p.status = ?
            AND (
                p.team_id IN (' . $strTeamIds . ')
                OR (ds.team_id IN (' . $strTeamIds . ') AND ds.status = ?)
            )
            ORDER BY p.id ASC',
            [Publication::STATUS_ACTIVE, Dataset::STATUS_ACTIVE]
        );

        $this->associatedDatasetIds = collect($linkPublications)
            ->filter(fn ($row) => !in_array((int) $row->ds_team_id, $teamIds))
            ->pluck('ds_id')
            ->unique()
            ->values()
            ->all();

        $mapped = collect($linkPublications)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'          => $group->first()->id,
                'paper_title' => $group->first()->paper_title,
                'authors'     => $group->first()->authors,
                'url'         => $group->first()->url,
                'relation'    => in_array($group->first()->p_team_id, $teamIds) ? 'owned' : 'associated',
                'team'        => $group->first()->t_id ? [
                    'id'   => $group->first()->t_id,
                    'name' => $group->first()->t_name,
                ] : null,
            ]);

        return [
            'owned'      => $mapped->where('relation', 'owned')->values()->all(),
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
        ];
    }

    private function associatedDatasets(array $aDatasetIds)
    {
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)
                ->whereIn('id', $aDatasetIds)
                ->with('team:id,name')
                ->select([
                    'id','is_cohort_discovery', 'user_id', 'team_id', 'datasetid'
                ])->get();

        foreach ($datasets as $dataset) {
            $metadataSummary = $dataset->latestVersion()['metadata']['metadata']['summary'] ?? [];
            $dataset['title'] = $this->getValueByPossibleKeys($metadataSummary, ['title'], '');
            $dataset['populationSize'] = $this->getValueByPossibleKeys($metadataSummary, ['populationSize'], '');
            $dataset['datasetType'] = $this->getValueByPossibleKeys($metadataSummary, ['datasetType'], '');
            $dataset['relation'] = 'associated';
            $dataset['team'] = [
                'id'   => $dataset->team->id,
                'name' => $dataset->team->name,
            ];
        }

        return $datasets;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/data_custodian_networks/{id}/info",
     *      description="Return a single DataCustodianNetwork - basic information",
     *      tags={"DataCustodianNetworks"},
     *      summary="DataCustodianNetworks@showInfoSummary",
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
     *                  @OA\Property(property="enabled", type="boolean", example="true"),
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

            $this->indexElasticDataCustodianNetwork((int) $dpc->id);

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

            $this->indexElasticDataCustodianNetwork((int) $id);

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

            $this->indexElasticDataCustodianNetwork((int) $id);

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

    public function getTeamsIds(DataProviderColl $dpc)
    {
        return DataProviderCollHasTeam::where(['data_provider_coll_id' => $dpc->id])->pluck('team_id')->toArray();
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
