<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Filter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Traits\PaginateFromArray;
use App\Http\Requests\Filter\GetFilter;
use App\Http\Requests\Filter\EditFilter;
use App\Http\Requests\Filter\CreateFilter;
use App\Http\Requests\Filter\DeleteFilter;
use App\Http\Requests\Filter\UpdateFilter;
use App\Http\Traits\RequestTransformation;

class FilterController extends Controller
{
    use RequestTransformation;
    use PaginateFromArray;

    /**
     * @OA\Get(
     *      path="/api/v1/filters",
     *      summary="List of system filters",
     *      description="Returns a list of filters enabled on the system",
     *      tags={"Filter"},
     *      summary="Filter@index",
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
     *            @OA\Property(property="current_page", type="integer", example="1"),
     *            @OA\Property(property="data", type="array",
     *              @OA\Items(
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="type", type="string", example="dataset"),
     *                  @OA\Property(property="keys", type="string", example="publisherName"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="buckets", type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="doc_count", type="integer", example="123"),
     *                          @OA\Property(property="key", type="string", example="Some publisher"),
     *                      )
     *                  )
     *              )
     *            ),
     *            @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests?page=1"),
     *            @OA\Property(property="from", type="integer", example="1"),
     *            @OA\Property(property="last_page", type="integer", example="1"),
     *            @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests?page=1"),
     *            @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *            @OA\Property(property="next_page_url", type="string", example="null"),
     *            @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests"),
     *            @OA\Property(property="per_page", type="integer", example="25"),
     *            @OA\Property(property="prev_page_url", type="string", example="null"),
     *            @OA\Property(property="to", type="integer", example="3"),
     *            @OA\Property(property="total", type="integer", example="3")
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = Filter::where('enabled', 1)->orderBy('type')->get()->toArray();

            $urlString = env('SEARCH_SERVICE_URL') . '/filters';

            $response = Http::withBody(
                json_encode(['filters' => $filters]),
                'application/json'
            )->post($urlString);

            $filterBuckets = isset($response->json()['filters']) ? $response->json()['filters'] : [];

            foreach ($filters as $i => $f) {
                $type = $f['type'];
                $keys = $f['keys'];
                if (isset($filterBuckets[$i][$type][$keys])) {
                    $filters[$i]['buckets'] = $filterBuckets[$i][$type][$keys]['buckets'];
                } else {
                    $filters[$i]['buckets'] = [];
                }
            }

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $filters, $perPage);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Filter get all',
            ]);

            return response()->json($paginatedData, 200);
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
     *      path="/api/v1/filters/{id}",
     *      summary="Return a single system filter",
     *      description="Return a single system filter",
     *      tags={"Filter"},
     *      summary="Filter@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="filter id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="filter id",
     *         )
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
     *                  @OA\Property(property="type", type="string", example="dataset"),
     *                  @OA\Property(property="keys", type="string", example="publisherName"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="buckets", type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="doc_count", type="integer", example="123"),
     *                          @OA\Property(property="key", type="string", example="Some publisher"),
     *                      )
     *                  )
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
    public function show(GetFilter $request, int $id): JsonResponse
    {
        try {
            $filter = Filter::findOrFail($id);
            if ($filter) {
                $urlString = env('SEARCH_SERVICE_URL') . '/filters';

                $response = Http::withBody(
                    json_encode(['filters' => [$filter->toArray()]]),
                    'application/json'
                )->post($urlString);

                $filterBuckets = isset($response->json()['filters'][0]) ?
                    $response->json()['filters'][0] : [];
                if (isset($filterBuckets[$filter['type']][$filter['keys']])) {
                    $filter['buckets'] = $filterBuckets[$filter['type']][$filter['keys']]['buckets'];
                } else {
                    $filter['buckets'] = [];
                }

                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Filter get ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $filter,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
     *      path="/api/v1/filters",
     *      summary="Create a new system filter",
     *      description="Creates a new system filter",
     *      tags={"Filter"},
     *      summary="Filter@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Filter definition",
     *          @OA\JsonContent(
     *              required={"type", "keys", "enabled"},
     *              @OA\Property(property="type", type="string", example="dataset"),
     *              @OA\Property(property="keys", type="string", example="purpose"),
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
    public function store(CreateFilter $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $filter = Filter::create([
                'type' => $input['type'],
                'keys' => $input['keys'],
                'enabled' => $input['enabled'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Filter ' . $filter->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $filter->id,
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
     *      path="/api/v1/filters/{id}",
     *      summary="Update a system filter",
     *      description="Update a system filter",
     *      tags={"Filter"},
     *      summary="Filter@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="filter id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="filter id",
     *         )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Filter definition",
     *          @OA\JsonContent(
     *              required={"type", "value", "keys", "enabled"},
     *              @OA\Property(property="type", type="string", example="dataset"),
     *              @OA\Property(property="keys", type="string", example="purpose"),
     *              @OA\Property(property="enabled", type="integer", example="1"),
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
     *                  @OA\Property(property="type", type="string", example="someType"),
     *                  @OA\Property(property="keys", type="string", example="someKey"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
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
    public function update(UpdateFilter $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            Filter::where('id', $id)->update([
                'type' => $input['type'],
                'keys' => $input['keys'],
                'enabled' => $input['enabled'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Filter ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Filter::where('id', $id)->first(),
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
     *      path="/api/v1/filters/{id}",
     *      summary="Edit a system filter",
     *      description="Edit a system filter",
     *      tags={"Filter"},
     *      summary="Filter@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="filter id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="filter id",
     *         )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Filter definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="type", type="string", example="dataset"),
     *              @OA\Property(property="keys", type="string", example="purpose"),
     *              @OA\Property(property="enabled", type="integer", example="1"),
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
     *                  @OA\Property(property="type", type="string", example="someType"),
     *                  @OA\Property(property="keys", type="string", example="someKey"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
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
    public function edit(EditFilter $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $arrayKeys = [
                'type',
                'keys',
                'enabled'
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Filter::where('id', $id)->update($array);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Filter ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Filter::where('id', $id)->first(),
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
     *      path="/api/v1/filters/{id}",
     *      summary="Delete a system filter",
     *      description="Delete a system filter",
     *      tags={"Filter"},
     *      summary="Filter@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="filter id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="filter id",
     *         )
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
    public function destroy(DeleteFilter $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            Filter::where(['id' => $id])->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Filter ' . $id . ' deleted',
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
}
