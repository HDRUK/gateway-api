<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Library;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\DataAccessTemplate;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\Library\EditLibrary;
use App\Http\Requests\Library\CreateLibrary;
use App\Http\Requests\Library\DeleteLibrary;
use App\Http\Requests\Library\GetLibrary;
use App\Http\Requests\Library\UpdateLibrary;

class LibraryController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/libraries",
     *      operationId="listLibraries",
     *      tags={"Library"},
     *      summary="Retrieve a list of libraries",
     *      description="Returns a paginated list of libraries along with associated datasets and teams.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Specify the number of libraries per page",
     *          required=false,
     *          @OA\Schema(
     *              type="integer",
     *              default=10
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="current_page", type="integer", example=1),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="1"),
     *                      @OA\Property(property="created_at", type="string", format="date-time", example="2023-04-03T12:00:00Z"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time", example="2023-04-03T12:00:00Z"),
     *                      @OA\Property(property="user_id", type="integer", example="123"),
     *                      @OA\Property(property="dataset_id", type="string", example="dataset12345"),
     *                      @OA\Property(property="dataset_status", type="string", example="ACTIVE"),
     *                      @OA\Property(property="data_provider_id", type="string", example="123"),
     *                      @OA\Property(property="data_provider_dar_status", type="boolean", example=false),
     *                      @OA\Property(property="data_provider_name", type="string", example="Team Name")
     *                  )
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="/api/v1/libraries?page=1"),
     *              @OA\Property(property="from", type="integer", example=1),
     *              @OA\Property(property="last_page", type="integer", example=10),
     *              @OA\Property(property="last_page_url", type="string", example="/api/v1/libraries?page=10"),
     *              @OA\Property(property="links", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="url", type="string", example=null),
     *                      @OA\Property(property="label", type="string", example="&laquo; Previous"),
     *                      @OA\Property(property="active", type="boolean", example=false)
     *                  )
     *              ),
     *              @OA\Property(property="next_page_url", type="string", example="/api/v1/libraries?page=2"),
     *              @OA\Property(property="path", type="string", example="/api/v1/libraries"),
     *              @OA\Property(property="per_page", type="integer", example=15),
     *              @OA\Property(property="prev_page_url", type="string", example=null),
     *              @OA\Property(property="to", type="integer", example=15),
     *              @OA\Property(property="total", type="integer", example=150)
     *          )
     *      )
     * )
     */

    public function index(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $perPage = (int) request('per_page', Config::get('constants.per_page'));

            $libraries = Library::where('user_id', $jwtUser['id'])
                ->with(['dataset.team']);


            $libraries = $libraries->paginate(function ($total) use ($perPage) {
                if ($perPage === -1) {
                    return $total;
                }
                return $perPage;
            }, ['*'], 'page');

            $transformedLibraries = $libraries->getCollection()->map(function (Library $library) {
                $dataset = $library->dataset;
                $team = $dataset->team;
                $teamPublishedDARTemplate = DataAccessTemplate::where('team_id', $team->id)->where('published', 1)->first();

                // Using dynamic attributes to avoid undefined property error
                $library->setAttribute('dataset_id', (int)$dataset->id);
                $library->setAttribute('dataset_name', $dataset->versions[0]->metadata['metadata']['summary']['shortTitle']);
                $library->setAttribute('dataset_status', $dataset->status);
                $library->setAttribute('data_provider_id', $team->id);
                $library->setAttribute('data_provider_dar_status', $team->uses_5_safes);
                $library->setAttribute('data_provider_name', $team->name);
                $library->setAttribute('data_provider_dar_enabled', (bool) $teamPublishedDARTemplate);
                $library->setAttribute('data_provider_published_dar_template', $team->is_question_bank);
                $library->setAttribute('data_provider_member_of', $team->member_of);
                $library->setAttribute('dataset_is_cohort_discovery', $dataset->is_cohort_discovery);
                $library->setAttribute('data_provider_dar_type', $teamPublishedDARTemplate?->template_type);

                unset($library->dataset);
                return $library;
            });

            $paginatedTransformedLibraries = new \Illuminate\Pagination\LengthAwarePaginator(
                $transformedLibraries,
                $libraries->total(),
                $libraries->perPage(),
                $libraries->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'library get all',
            ]);

            return response()->json($paginatedTransformedLibraries);
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
     * @OA\Get(
     *      path="/api/v1/libraries/{id}",
     *      summary="Return a single library",
     *      description="Return a single library",
     *      tags={"Library"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="library id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="library id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="string", format="date-time", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="user_id", type="integer", example="123"),
     *                  @OA\Property(
     *                          property="dataset",
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example="10"),
     *                          @OA\Property(property="status", type="string", example="ACTIVE"),
     *                          @OA\Property(
     *                              property="team",
     *                              type="object",
     *                              @OA\Property(property="id", type="integer", example="5"),
     *                              @OA\Property(property="pid", type="string", example="PID12345"),
     *                              @OA\Property(property="access_requests_management", type="boolean", example=false)
     *                          )
     *                      )
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
    public function show(GetLibrary $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $jwtUserIsAdmin = $jwtUser['is_admin'];

            // Fetch the library with dataset and team
            $library = Library::with(['dataset.team'])
                ->where('id', $id)
                ->firstOrFail();

            if (!$jwtUserIsAdmin && $library['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to view this library');
            }

            $dataset = $library->dataset->first();
            $team = $dataset->team;

            // Using dynamic attributes to avoid undefined property error
            $library->setAttribute('dataset_id', (int)$dataset->datasetid);
            $library->setAttribute('dataset_status', $dataset->status);
            $library->setAttribute('data_provider_id', $team->id);
            $library->setAttribute('data_provider_dar_status', $team->uses_5_safes);
            $library->setAttribute('data_provider_name', $team->name);
            $library->setAttribute('dataset_name', $dataset->versions[0]->metadata['metadata']['summary']['shortTitle']);
            $library->setAttribute('data_provider_dar_enabled', $team->is_question_bank);
            $library->setAttribute('data_provider_member_of', $team->member_of);
            $library->setAttribute('dataset_is_cohort_discovery', $dataset->is_cohort_discovery);

            unset($library->dataset);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'library get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $library,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        } catch (NotFoundException $e) {
            return response()->json([
                'message' => 'not found',
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred',
            ], Config::get('statuscodes.STATUS_INTERNAL_SERVER_ERROR.code'));
        }
    }



    /**
     * @OA\Post(
     *      path="/api/v1/libraries",
     *      summary="Create a new library",
     *      description="Creates a new library",
     *      tags={"Library"},
     *      summary="Library@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="library definition",
     *          @OA\JsonContent(
     *              required={"dataset_id"},
     *                  @OA\Property(property="dataset_id", type="integer", example="123"),
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
    public function store(CreateLibrary $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $library = Library::updateOrCreate([
                'user_id' => (int)$jwtUser['id'],
                'dataset_id' => $input['dataset_id'],
                'deleted_at' => null,
            ], []);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'library ' . $library->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $library->id,
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
     *      path="/api/v1/libraries/{id}",
     *      summary="Update a library",
     *      description="Update a library",
     *      tags={"Library"},
     *      summary="Library@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="library id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="library id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="library definition",
     *          @OA\JsonContent(
     *              required={"dataset_id"},
     *                  @OA\Property(property="dataset_id", type="integer", example="123"),
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
     *                  @OA\Property(property="user_id", type="integer", example="123"),
     *                  @OA\Property(property="dataset", type="array", @OA\Items()),
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
    public function update(UpdateLibrary $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $library = Library::where('id', $id)->first();
            if ($library['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to edit this library');
            }
            $library->update([
                'user_id' => (int)$jwtUser['id'],
                'dataset_id' => $input['dataset_id'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'library ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Library::where('id', $id)->first()
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
     *      path="/api/v1/libraries/{id}",
     *      summary="Edit a library",
     *      description="Edit a library",
     *      tags={"Library"},
     *      summary="Library@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="library id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="library id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="library definition",
     *          @OA\JsonContent(
     *              required={"dataset_id"},
     *                  @OA\Property(property="dataset_id", type="integer", example="123"),
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
     *                  @OA\Property(property="user_id", type="integer", example="123"),
     *                  @OA\Property(property="dataset", type="array", @OA\Items()),
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
    public function edit(EditLibrary $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $arrayKeys = [
                'user_id',
                'dataset_id',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            $library = Library::where('id', $id)->first();
            if ($library['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to edit this library');
            }

            $library->update($array);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'library ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Library::where('id', $id)->first()
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
     *      path="/api/v1/libraries/{id}",
     *      summary="Delete a library",
     *      description="Delete a library",
     *      tags={"Library"},
     *      summary="Library@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="library id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="library id",
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
    public function destroy(DeleteLibrary $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $library = Library::findOrFail($id);
            if ($library) {

                if ($library['user_id'] != $jwtUser['id']) {
                    throw new UnauthorizedException('You do not have permission to delete this library');
                }

                if ($library->delete()) {
                    Auditor::log([
                        'user_id' => (int)$jwtUser['id'],
                        'action_type' => 'DELETE',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => 'library ' . $id . ' deleted',
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
