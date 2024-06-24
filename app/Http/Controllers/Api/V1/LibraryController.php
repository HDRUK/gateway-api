<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Library;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
     *      summary="List of libraries",
     *      description="Returns a list of libraries on the system",
     *      tags={"Library"},
     *      summary="Library@index",
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
     *                      @OA\Property(property="user_id", type="integer", example="123"),
     *                      @OA\Property(property="dataset", type="array", @OA\Items()),
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
                $libraries = Library::with('dataset');
            } else {
                $libraries = Library::where('user_id', $jwtUser['id'])
                    ->with('dataset');
            }

            $libraries = $libraries->paginate($perPage);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "library get all",
            ]);

            return response()->json(
                $libraries,
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/libraries/{id}",
     *      summary="Return a single library",
     *      description="Return a single library",
     *      tags={"Library"},
     *      summary="Library@show",
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
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="user_id", type="integer", example="123"),
     *                  @OA\Property(property="dataset", type="array", @OA\Items()),
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $jwtUserIsAdmin = $jwtUser['is_admin'];

            $Library = Library::where('id', $id)->with(['dataset'])->first();
            if (!$jwtUserIsAdmin && $Library['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to view this library');
            } 
            
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "library get " . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $Library,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $Library = Library::create([
                'user_id' => (int) $jwtUser['id'],
                'dataset_id' => $input['dataset_id']
            ]);
            
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "library " . $Library->id . " created",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $Library->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $Library = Library::where('id', $id)->first();
            if ($Library['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to edit this library');
            }
            $Library->update([
                'user_id' => (int) $jwtUser['id'],
                'dataset_id' => $input['dataset_id'],
            ]);
            
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "library " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Library::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $arrayKeys = [
                'user_id',
                'dataset_id',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            $Library = Library::where('id', $id)->first();
            if ($Library['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to edit this library');
            }
            
            $Library->update($array);
            
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "library " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Library::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
    
            $Library = Library::findOrFail($id);
            if ($Library) {

                if ($Library['user_id'] != $jwtUser['id']) {
                    throw new UnauthorizedException('You do not have permission to delete this library');
                }

                if ($Library->save()) {
                    Auditor::log([
                        'user_id' => (int) $jwtUser['id'],
                        'action_type' => 'DELETE',
                        'action_name' => class_basename($this) . '@'.__FUNCTION__,
                        'description' => "library " . $id . " deleted",
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

