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

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\DataProviderCollHasTeam;
use MetadataManagementController as MMC;

class DataProviderCollController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/data_provider_colls",
     *      summary="List of DataProviderColl's",
     *      description="Returns a list of DataProviderColls enabled on the system",
     *      tags={"DataProviderColl"},
     *      summary="DataProviderColl@index",
     *      security={{"bearerAuth":{}}},
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
     *                      @OA\Property(property="enabled", type="boolean", example="1")
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

            $perPage = request('perPage', Config::get('constants.per_page'));
            $dps = DataProviderColl::with([
                'teams'
                ])->where('enabled', 1)
                ->paginate((int) $perPage, ['*'], 'page');

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProviderColl list'
            ]);

            return response()->json(
                $dps,
            );

        } catch (Exception $e) {
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
     *                  @OA\Property(property="enabled", type="boolean", example="1")
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
            $dp = DataProviderColl::select('id', 'name', 'img_url')->where([
                'id' => $id,
                'enabled' => 1,
            ])->first();

            $newDps = $this->getTeams($dp);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProviderColl get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $newDps,
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getTeams(DataProviderColl $dp)
    {
        $return = [];
        $idTeams = DataProviderCollHasTeam::where(['data_provider_coll_id' => $dp->id])->pluck('team_id')->toArray();

        foreach ($idTeams as $idTeam) {
            $tmp = [];

            $team = Team::select('id', 'name')->where(['id' => $idTeam])->first();
            $tmp['id'] = $team->id;
            $tmp['name'] = $team->name;
            $datasets = $this->getDatasets((int) $team->id);
            $tmp['datasets'] = $datasets;
            $return[] = $tmp;

            unset($tmp);
        }

        return $return;
    }

    public function getDatasets(int $teamId)
    {
        $return = [];

        $datasets = Dataset::select('id', 'status')->where(['team_id' => $teamId])->get();

        foreach ($datasets as $dataset) {
            $tmp = [];

            $tmp['id'] = $dataset->id;
            $tmp['status'] = $dataset->status;
            $dataset = Dataset::where(['id' => $dataset->id])
                ->with(['durs', 'collections', 'publications'])
                ->first();
            $version = $dataset->latestVersion();
            $withLinks = DatasetVersion::where('id', $version['id'])
                ->with(['linkedDatasetVersions'])
                ->first();
            if ($withLinks) {
                $dataset->versions = [$withLinks];
            }
            $tmp['dataset'] = $dataset;
            $return[] = $tmp;

            unset($tmp);
        }

        return $return;
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
     *              required={"name", "enabled", "team_ids"},
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="enabled", type="boolean", example="true"),
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $dps = DataProviderColl::create([
                'enabled' => $input['enabled'],
                'name' => $input['name'],
                'img_url' => $input['img_url'],
            ]);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProvider ' . $dps->id . ' created',
            ]);

            foreach ($input['team_ids'] as $teamId) {
                DataProviderCollHasTeam::create([
                    'data_provider_coll_id' => $dps->id,
                    'team_id' => $teamId,
                ]);

                Auditor::log([
                    'user_id' => (int) $jwtUser['id'],
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataProviderCollHasTeam ' . $dps->id . '/' . $teamId . ' created',
                ]);
            }

            $this->indexElasticDataProviderColl((int) $dps->id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $dps->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
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
     *              required={"name", "enabled", "team_ids"},
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="enabled", type="string", example="true"),
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
     *                  @OA\Property(property="enabled", type="boolean", example="1")
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $dps = DataProviderColl::where('id', $id)->first();

            $dps->enabled = $input['enabled'];
            $dps->name = $input['name'];
            $dps->img_url = $input['img_url'];
            $dps->save();

            if (isset($input['team_ids']) && !empty($input['team_ids'])) {
                DataProviderCollHasTeam::where('data_provider_coll_id', $dps->id)->delete();

                foreach ($input['team_ids'] as $teamId) {
                    DataProviderCollHasTeam::create([
                        'data_provider_coll_id' => $dps->id,
                        'team_id' => $teamId,
                    ]);
                }
            }

            $this->indexElasticDataProviderColl((int) $id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dps,
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
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
     *              @OA\Property(property="enabled", type="string", example="true"),
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
     *                  @OA\Property(property="enabled", type="boolean", example="1")
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $dps = DataProviderColl::where('id', $id)->first();

            $dps->enabled = (isset($input['enabled']) ? $input['enabled'] : $dps->enabled);
            $dps->name = (isset($input['name']) ? $input['name'] : $dps->name);
            $dps->img_url = (isset($input['img_url']) ? $input['img_url'] : $dps->img_url);
            $dps->save();

            if (isset($input['team_ids']) && !empty($input['team_ids'])) {
                DataProviderCollHasTeam::where('data_provider_coll_id', $dps->id)->delete();

                foreach ($input['team_ids'] as $teamId) {
                    DataProviderCollHasTeam::create([
                        'data_provider_coll_id' => $dps->id,
                        'team_id' => $teamId,
                    ]);
                }
            }

            $this->indexElasticDataProviderColl((int) $id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dps,
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            DataProviderCollHasTeam::where(['data_provider_coll_id' => $id])->delete();
            DataProviderColl::where(['id' => $id])->delete();

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProvider ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
            
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data provider document into elastic index
     *
     * @param integer $id
     * @return void
     */
    private function indexElasticDataProviderColl(int $id): void 
    {
        $provider = DataProviderColl::where('id', $id)->with('teams')->first();

        $datasetTitles = array();
        $locations = array();
        foreach ($provider['teams'] as $team) {
            $datasets = Dataset::where('team_id', $team['id'])->with(['versions', 'spatialCoverage'])->get();
            foreach ($datasets as $dataset) {
                $metadata = $dataset['versions'][0];
                $datasetTitles[] = $metadata['metadata']['metadata']['summary']['shortTitle'];
                foreach ($dataset['spatialCoverage'] as $loc) {
                    if (!in_array($loc['region'], $locations)) {
                        $locations[] = $loc['region'];
                    }
                }
            }
        }
        usort($datasetTitles, 'strcasecmp');

        try {
            $toIndex = [
                'name' => $provider['name'],
                'datasetTitles' => $datasetTitles,
                'geographicLocation' => $locations,
            ];
            $params = [
                'index' => 'dataprovidercoll',
                'id' => $id,
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
