<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;

use App\Models\DataProvider;
use App\Models\DataProviderHasTeam;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Http\Controllers\Controller;

class DataProviderController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/data_providers",
     *      summary="List of DataProvider's",
     *      description="Returns a list of DataProviders enabled on the system",
     *      tags={"DataProvider"},
     *      summary="DataProvider@index",
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
            $dps = DataProvider::with([
                'teams'
                ])->where('enabled', 1)
                ->paginate((int) $perPage, ['*'], 'page');

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProvider list'
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
     *      path="/api/v1/data_providers/{id}",
     *      summary="Return a single DataProvider",
     *      description="Return a single DataProvider",
     *      tags={"DataProvider"},
     *      summary="DataProvider@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProvider ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProvider ID",
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
            $dps = DataProvider::with([
                'teams',
                ])->where('id', $id)->firstOrFail();

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProvider get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dps
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/data_providers",
     *      summary="Create a new DataProvider",
     *      description="Creates a new DataProvider",
     *      tags={"DataProvider"},
     *      summary="DataProvider@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataProvider definition",
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

            $dps = DataProvider::create([
                'enabled' => $input['enabled'],
                'name' => $input['name'],
                'img_url' => $input['img_url'],
            ]);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProvider ' . $dps->id . ' created',
            ]);

            foreach ($input['team_ids'] as $teamId) {
                DataProviderHasTeam::create([
                    'data_provider_id' => $dps->id,
                    'team_id' => $teamId,
                ]);

                Auditor::log([
                    'user_id' => $jwtUser['id'],
                    'action_type' => 'CREATE',
                    'action_service' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataProviderHasTeam ' . $dps->id . '/' . $teamId . ' created',
                ]);
            }

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
     *      path="/api/v1/data_providers/{id}",
     *      summary="Update a DataProvider",
     *      description="Update a DataProvider",
     *      tags={"DataProvider"},
     *      summary="DataProvider@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProvider ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProvider ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataProvider definition",
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

            $dps = DataProvider::where('id', $id)->first();

            $dps->enabled = $input['enabled'];
            $dps->name = $input['name'];
            $dps->img_url = $input['img_url'];
            $dps->save();

            if (isset($input['team_ids']) && !empty($input['team_ids'])) {
                DataProviderHasTeam::where('data_provider_id', $dps->id)->delete();

                foreach ($input['team_ids'] as $teamId) {
                    DataProviderHasTeam::create([
                        'data_provider_id' => $dps->id,
                        'team_id' => $teamId,
                    ]);
                }
            }

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
     *      path="/api/v1/data_providers/{id}",
     *      summary="Edit a DataProvider",
     *      description="Edit a DataProvider",
     *      tags={"DataProvider"},
     *      summary="DataProvider@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProvider ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProvider ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataProvider definition",
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

            $dps = DataProvider::where('id', $id)->first();

            $dps->enabled = (isset($input['enabled']) ? $input['enabled'] : $dps->enabled);
            $dps->name = (isset($input['name']) ? $input['name'] : $dps->name);
            $dps->img_url = (isset($input['img_url']) ? $input['img_url'] : $dps->img_url);
            $dps->save();

            if (isset($input['team_ids']) && !empty($input['team_ids'])) {
                DataProviderHasTeam::where('data_provider_id', $dps->id)->delete();

                foreach ($input['team_ids'] as $teamId) {
                    DataProviderHasTeam::create([
                        'data_provider_id' => $dps->id,
                        'team_id' => $teamId,
                    ]);
                }
            }

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
     *      path="/api/v1/data_providers/{id}",
     *      summary="Delete a DataProvider",
     *      description="Delete a DataProvider",
     *      tags={"DataProvider"},
     *      summary="DataProvider@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProvider ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProvider ID",
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

            DataProviderHasTeam::where(['data_provider_id' => $id])->delete();
            DataProvider::where(['id' => $id])->delete();

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataProvider ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
            
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
