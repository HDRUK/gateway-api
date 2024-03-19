<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Models\SavedSearch;
use App\Models\SavedSearchHasFilter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SavedSearchController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/saved_searches",
     *      summary="List of saved searches",
     *      description="Returns a list of saved searches enabled on the system",
     *      tags={"SavedSearch"},
     *      summary="SavedSearch@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="perPage",
     *          in="query",
     *          description="Specify number of results per page",
     *          @OA\Schema(type="integer")
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
     *                      @OA\Property(property="name", type="string", example="Name"),
     *                      @OA\Property(property="search_term", type="string", example="Example Search"),
     *                      @OA\Property(property="search_endpoint", type="string", example="datasets"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                      @OA\Property(property="filters", type="array", example="[1,2]", @OA\Items()),
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
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $jwtUserIsAdmin = $jwtUser['is_admin'];

            $perPage = request('perPage', Config::get('constants.per_page'));
            if ($jwtUserIsAdmin) {
                $saved_searches = SavedSearch::where('enabled', 1)->with('filters');
            } else {
                $saved_searches = SavedSearch::where('enabled', 1)
                    ->where('user_id', $jwtUser['id'])
                    ->with('filters');
            }

            $filterName = $request->query('name', null);
            if (!empty($filterName)) {
                $saved_searches = $saved_searches->where('name', 'like', '%' . $filterName . '%');
            }
            $saved_searches = $saved_searches->paginate($perPage);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Saved Search get all",
            ]);

            return response()->json(
                $saved_searches,
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/saved_searches/{id}",
     *      summary="Return a single saved search",
     *      description="Return a single saved search",
     *      tags={"SavedSearch"},
     *      summary="SavedSearch@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="saved search id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="saved search id",
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
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="search_term", type="string", example="Example Search"),
     *                  @OA\Property(property="search_endpoint", type="string", example="datasets"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="filters", type="array", example="[1,2]", @OA\Items()),
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
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $jwtUserIsAdmin = $jwtUser['is_admin'];

            $saved_search = SavedSearch::where(['id' => $id,])->with(['filters'])->get();
            if (!$jwtUserIsAdmin && $saved_search['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to view this saved search');
            } 
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Saved Search get " . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $saved_search,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/saved_searches",
     *      summary="Create a new saved search",
     *      description="Creates a new saved search",
     *      tags={"SavedSearch"},
     *      summary="SavedSearch@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Saved search definition",
     *          @OA\JsonContent(
     *              required={"name", "enabled"},
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="search_endpoint", type="string", example="datasets"),
     *              @OA\Property(property="filters", type="array",
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", example="1"),
     *                      @OA\Property(property="terms", type="array", example="['A publisher']", @OA\Items()),
     *                  ),
     *              ),
     *              @OA\Property(property="enabled", type="boolean", example="true"),
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

            $arraySearch = array_filter($input, function ($key) {
                return $key !== 'filters';
            }, ARRAY_FILTER_USE_KEY);
            $arraySearchFilter = $input['filters'];

            $saved_search = SavedSearch::create([
                'name' => $input['name'],
                'search_term' => $input['search_term'],
                'search_endpoint' => $input['search_endpoint'],
                'enabled' => $input['enabled'],
                'user_id' => $jwtUser['id'],
            ]);

            if ($saved_search) {
                foreach ($arraySearchFilter as $filter) {
                    SavedSearchHasFilter::updateOrCreate([
                        'saved_search_id' => (int) $saved_search->id,
                        'filter_id' => (int) $filter['id'],
                        'terms' => $filter['terms'],
                    ]);
                }
            } else {
                throw new NotFoundException();
            }
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Saved Search " . $saved_search->id . " created",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $saved_search->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/saved_searches/{id}",
     *      summary="Update a saved search",
     *      description="Update a saved search",
     *      tags={"SavedSearch"},
     *      summary="SavedSearch@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="saved search id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="saved search id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Saved search definition",
     *          @OA\JsonContent(
     *              required={"name", "enabled"},
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="search_endpoint", type="string", example="datasets"),
     *              @OA\Property(property="filters", type="array",
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", example="1"),
     *                      @OA\Property(property="terms", type="array", example="['A publisher']", @OA\Items()),
     *                  ),
     *              ),
     *              @OA\Property(property="enabled", type="string", example="true"),
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
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="search_term", type="string", example="Example Search"),
     *                  @OA\Property(property="search_endpoint", type="string", example="datasets"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="filters", type="array", example="[1,2]", @OA\Items()),
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

            $saved_search = SavedSearch::where('id', $id)->first();
            if ($saved_search['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to edit this saved search');
            }
            $saved_search->update([
                'name' => $input['name'],
                'search_term' => $input['search_term'],
                'search_endpoint' => $input['search_endpoint'],
                'enabled' => $input['enabled'],
                'user_id' => $jwtUser['id'],
            ]);

            $arraySearchFilter = $input['filters'];
            SavedSearchHasFilter::where('saved_search_id', $id)->delete();
            foreach ($arraySearchFilter as $filter) {
                SavedSearchHasFilter::updateOrCreate([
                    'saved_search_id' => (int) $id,
                    'filter_id' => (int) $filter['id'],
                    'terms' => $filter['terms'],
                ]);
            }
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Saved Search " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => SavedSearch::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/saved_searches/{id}",
     *      summary="Edit a saved search",
     *      description="Edit a saved search",
     *      tags={"SavedSearch"},
     *      summary="SavedSearch@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="saved search id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="saved search id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Saved search definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="filters", type="array",
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", example="1"),
     *                      @OA\Property(property="terms", type="array", example="['A publisher']", @OA\Items()),
     *                  ),
     *              ),
     *              @OA\Property(property="enabled", type="string", example="true"),
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
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="search_term", type="string", example="Example Search"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="filters", type="array", example="[1,2]", @OA\Items()),
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

            $arrayKeys = [
                'name',
                'search_term',
                'search_endpoint',
                'enabled',
                'filters',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            $saved_search = SavedSearch::where('id', $id)->first();
            if ($saved_search['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to edit this saved search');
            }
            
            $saved_search->update($array);

            $arraySearchFilter = array_key_exists('filters', $input) ? $input['filters'] : [];

            SavedSearchHasFilter::where('saved_search_id', $id)->delete();
            foreach ($arraySearchFilter as $filter) {
                SavedSearchHasFilter::updateOrCreate([
                    'saved_search_id' => (int) $id,
                    'filter_id' => (int) $filter['id'],
                    'terms' => $filter['terms'],
                ]);
            }
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Saved Search " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => SavedSearch::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/saved_searches/{id}",
     *      summary="Delete a saved search",
     *      description="Delete a saved search",
     *      tags={"SavedSearch"},
     *      summary="SavedSearch@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="saved search id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="saved search id",
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
    
            $saved_search = SavedSearch::findOrFail($id);
            if ($saved_search) {

                if ($saved_search['user_id'] != $jwtUser['id']) {
                    throw new UnauthorizedException('You do not have permission to delete this saved search');
                }

                $saved_search->enabled = false;
                if ($saved_search->save()) {
                    Auditor::log([
                        'user_id' => $jwtUser['id'],
                        'action_type' => 'DELETE',
                        'action_service' => class_basename($this) . '@'.__FUNCTION__,
                        'description' => "Saved Search " . $id . " deleted",
                    ]);

                    return response()->json([
                        'message' => Config::get('statuscodes.STATUS_OK.message'),
                    ], Config::get('statuscodes.STATUS_OK.code'));
                }
    
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
                ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
            }
    
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
