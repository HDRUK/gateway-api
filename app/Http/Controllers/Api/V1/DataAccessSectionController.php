<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\DataAccessSection\GetDataAccessSection;
use App\Http\Requests\DataAccessSection\EditDataAccessSection;
use App\Http\Requests\DataAccessSection\CreateDataAccessSection;
use App\Http\Requests\DataAccessSection\DeleteDataAccessSection;
use App\Http\Requests\DataAccessSection\UpdateDataAccessSection;
use App\Models\DataAccessSection;

class DataAccessSectionController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/dar/sections",
     *      summary="List of DAR sections",
     *      description="List of DAR sections",
     *      tags={"DataAccessSection"},
     *      summary="DataAccessSection@index",
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
     *                      @OA\Property(property="name", type="string", example="Safe People"),
     *                      @OA\Property(property="description", type="string", example="Who has access?"),
     *                      @OA\Property(property="parent_section", type="integer", example="1"),
     *                      @OA\Property(property="order", type="integer", example="1"),
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

            $sections = DataAccessSection::paginate(
                Config::get('constants.per_page'),
                ['*'],
                'page'
            );

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessSection get all',
            ]);

            return response()->json(
                $sections
            );
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
     *      path="/api/v1/dar/sections/{id}",
     *      summary="Return a single DAR section",
     *      description="Return a single DAR section",
     *      tags={"DataAccessSection"},
     *      summary="DataAccessSection@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR section id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR section id",
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
     *                  @OA\Property(property="name", type="string", example="Safe People"),
     *                  @OA\Property(property="description", type="string", example="Who has access?"),
     *                  @OA\Property(property="parent_section", type="integer", example="1"),
     *                  @OA\Property(property="order", type="integer", example="1"),
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
    public function show(GetDataAccessSection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $section = DataAccessSection::findOrFail($id);

            if ($section) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataAccessSection get ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $section,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
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

    /**
     * @OA\Post(
     *      path="/api/v1/dar/sections",
     *      summary="Create a new DAR section",
     *      description="Creates a new DAR section",
     *      tags={"DataAccessSection"},
     *      summary="DataAccessSection@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessSection definition",
     *          @OA\JsonContent(
     *              required={"name","description","order"},
     *              @OA\Property(property="name", type="string", example="Safe People"),
     *              @OA\Property(property="description", type="string", example="Who has access?"),
     *              @OA\Property(property="parent_section", type="integer", example="1"),
     *              @OA\Property(property="order", type="integer", example="1"),
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
    public function store(CreateDataAccessSection $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $section = DataAccessSection::create([
                'name' => $input['name'],
                'description' => $input['description'],
                'parent_section' => $input['parent_section'] ?? null,
                'order' => $input['order'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessSection ' . $section->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $section->id,
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
     *      path="/api/v1/dar/sections/{id}",
     *      summary="Update a system DAR section",
     *      description="Update a system DAR section",
     *      tags={"DataAccessSection"},
     *      summary="DataAccessSection@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR section id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR section id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessSection definition",
     *          @OA\JsonContent(
     *              required={"name","description","order"},
     *              @OA\Property(property="name", type="string", example="Safe People"),
     *              @OA\Property(property="description", type="string", example="Who has access?"),
     *              @OA\Property(property="parent_section", type="integer", example="1"),
     *              @OA\Property(property="order", type="integer", example="1"),
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
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Safe People"),
     *                  @OA\Property(property="description", type="string", example="Who has access?"),
     *                  @OA\Property(property="parent_section", type="integer", example="1"),
     *                  @OA\Property(property="order", type="integer", example="1"),
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
    public function update(UpdateDataAccessSection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $section = DataAccessSection::findOrFail($id);

            $section->update([
                'name' => $input['name'],
                'description' => $input['description'],
                'parent_section' => $input['parent_section'] ?? $section->parent_section,
                'order' => $input['order'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessSection ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessSection::where('id', $id)->first(),
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
     *      path="/api/v1/dar/sections/{id}",
     *      summary="Edit a system DAR section",
     *      description="Edit a system DAR section",
     *      tags={"DataAccessSection"},
     *      summary="DataAccessSection@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR section id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR section id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessSection definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Safe People"),
     *              @OA\Property(property="description", type="string", example="Who has access?"),
     *              @OA\Property(property="parent_section", type="integer", example="1"),
     *              @OA\Property(property="order", type="integer", example="1"),
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
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Safe People"),
     *                  @OA\Property(property="description", type="string", example="Who has access?"),
     *                  @OA\Property(property="parent_section", type="integer", example="1"),
     *                  @OA\Property(property="order", type="integer", example="1"),
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
    public function edit(EditDataAccessSection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $section = DataAccessSection::findOrFail($id);

            $arrayKeys = [
                'name',
                'description',
                'parent_section',
                'order',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $section->update($array);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessSection ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessSection::where('id', $id)->first(),
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
     *      path="/api/v1/dar/sections/{id}",
     *      summary="Delete a system DAR section",
     *      description="Delete a system DAR section",
     *      tags={"DataAccessSection"},
     *      summary="DataAccessSection@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR section id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR section id",
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
    public function destroy(DeleteDataAccessSection $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $section = DataAccessSection::findOrFail($id);
            $section->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessSection ' . $id . ' deleted',
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
