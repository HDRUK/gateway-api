<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\ShortList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\ShortList\EditShortList;
use App\Http\Requests\ShortList\CreateShortList;
use App\Http\Requests\ShortList\DeleteShortList;
use App\Http\Requests\ShortList\GetShortList;
use App\Http\Requests\ShortList\UpdateShortList;

class ShortListController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/short_lists",
     *      summary="List of short lists",
     *      description="Returns a list of short lists on the system",
     *      tags={"ShortList"},
     *      summary="ShortList@index",
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
                $shortLists = ShortList::with('dataset');
            } else {
                $shortLists = ShortList::where('user_id', $jwtUser['id'])
                    ->with('dataset');
            }

            $shortLists = $shortLists->paginate($perPage);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "short list get all",
            ]);

            return response()->json(
                $shortLists,
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/short_lists/{id}",
     *      summary="Return a single short list",
     *      description="Return a single short list",
     *      tags={"ShortList"},
     *      summary="ShortList@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="short list id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="short list id",
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
    public function show(GetShortList $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $jwtUserIsAdmin = $jwtUser['is_admin'];

            $shortList = ShortList::where('id', $id)->with(['dataset'])->first();
            if (!$jwtUserIsAdmin && $shortList['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to view this short list');
            } 
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "short list get " . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $shortList,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/short_lists",
     *      summary="Create a new short list",
     *      description="Creates a new short list",
     *      tags={"ShortList"},
     *      summary="ShortList@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="short list definition",
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
    public function store(CreateShortList $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $shortList = ShortList::create([
                'user_id' => $jwtUser['id'],
                'dataset_id' => $input['dataset_id']
            ]);
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "short list " . $shortList->id . " created",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $shortList->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/short_lists/{id}",
     *      summary="Update a short list",
     *      description="Update a short list",
     *      tags={"ShortList"},
     *      summary="ShortList@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="short list id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="short list id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="short list definition",
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
    public function update(UpdateShortList $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $shortList = ShortList::where('id', $id)->first();
            if ($shortList['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to edit this short list');
            }
            $shortList->update([
                'user_id' => $jwtUser['id'],
                'dataset_id' => $input['dataset_id'],
            ]);
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "short list " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => ShortList::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/short_lists/{id}",
     *      summary="Edit a short list",
     *      description="Edit a short list",
     *      tags={"ShortList"},
     *      summary="ShortList@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="short list id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="short list id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="short list definition",
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
    public function edit(EditShortList $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $arrayKeys = [
                'user_id',
                'dataset_id',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            $shortList = ShortList::where('id', $id)->first();
            if ($shortList['user_id'] != $jwtUser['id']) {
                throw new UnauthorizedException('You do not have permission to edit this short list');
            }
            
            $shortList->update($array);
            
            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "short list " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => ShortList::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/short_lists/{id}",
     *      summary="Delete a short list",
     *      description="Delete a short list",
     *      tags={"ShortList"},
     *      summary="ShortList@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="short list id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="short list id",
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
    public function destroy(DeleteShortList $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
    
            $shortList = ShortList::findOrFail($id);
            if ($shortList) {

                if ($shortList['user_id'] != $jwtUser['id']) {
                    throw new UnauthorizedException('You do not have permission to delete this short list');
                }

                if ($shortList->save()) {
                    Auditor::log([
                        'user_id' => $jwtUser['id'],
                        'action_type' => 'DELETE',
                        'action_service' => class_basename($this) . '@'.__FUNCTION__,
                        'description' => "short list " . $id . " deleted",
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

