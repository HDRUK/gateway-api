<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\License\CreateLicense;
use App\Http\Requests\License\DeleteLicense;
use App\Http\Requests\License\EditLicense;
use App\Http\Requests\License\GetLicense;
use App\Http\Requests\License\UpdateLicense;

class LicenseController extends Controller
{
    use RequestTransformation;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *      path="/api/v1/licenses",
     *      summary="List of system licenses",
     *      description="Returns a list of licenses available",
     *      tags={"License"},
     *      summary="License@index",
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example="1"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="code", type="string", example="HDR_CATEGORY_AVAILABLE_UPON_REQUEST"),
     *                      @OA\Property(property="label", type="string", example="Available upon request"),
     *                      @OA\Property(property="valid_since", type="datetime", example="2024-04-15 00:00:00"),
     *                      @OA\Property(property="valid_until", type="datetime", example="2024-04-15 00:00:00"),
     *                      @OA\Property(property="definition", type="string", example="Access to the software ..."),
     *                      @OA\Property(property="verified", type="boolean", example="1"),
     *                      @OA\Property(property="origin", type="string", example="HDR"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  )
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/licenses?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/licenses?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/licenses"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $licenses = License::where('valid_until', null)->paginate(Config::get('constants.per_page'), ['*'], 'page');

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Licenses get all',
            ]);

            return response()->json($licenses, 200);
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
     *      path="/api/v1/licenses/{id}",
     *      summary="Return a single license",
     *      description="Return a single license",
     *      tags={"License"},
     *      summary="License@show",
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="License ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="License ID",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="code", type="string", example="HDR_CATEGORY_AVAILABLE_UPON_REQUEST"),
     *                  @OA\Property(property="label", type="string", example="Available upon request"),
     *                  @OA\Property(property="valid_since", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="valid_until", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="definition", type="string", example="Access to the software ..."),
     *                  @OA\Property(property="verified", type="boolean", example="1"),
     *                  @OA\Property(property="origin", type="string", example="HDR"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
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
    public function show(GetLicense $request, int $id): JsonResponse
    {
        try {
            $license = License::where(['id' => $id])->first();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'License get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $license,
            ], Config::get('statuscodes.STATUS_OK.code'));
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
     *      path="/api/v1/licenses",
     *      summary="Create a new license",
     *      description="Creates a new license",
     *      tags={"License"},
     *      summary="License@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="License definition",
     *          @OA\JsonContent(
     *              required={"code", "label", "valid_since", "definition", "origin"},
     *                  @OA\Property(property="code", type="string", example="HDR_CATEGORY_AVAILABLE_UPON_REQUEST"),
     *                  @OA\Property(property="label", type="string", example="Available upon request"),
     *                  @OA\Property(property="valid_since", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="valid_until", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="definition", type="string", example="Access to the software ..."),
     *                  @OA\Property(property="origin", type="string", example="HDR"),
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
    public function store(CreateLicense $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $license = License::create([
                'code' => strtoupper($input['code']),
                'label' => $input['label'],
                'valid_since' => $input['valid_since'],
                'valid_until' => array_key_exists('valid_until', $input) ? $input['valid_until'] : null,
                'definition' => $input['definition'],
                'origin' => $input['origin'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'License ' . $license->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $license->id,
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
     *      path="/api/v1/licenses/{id}",
     *      summary="Update a tool license",
     *      description="Update a tool license",
     *      tags={"License"},
     *      summary="License@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="license id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="license id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Category definition",
     *          @OA\JsonContent(
     *              required={"code", "label", "valid_since", "definition", "origin"},
     *                  @OA\Property(property="code", type="string", example="HDR_CATEGORY_AVAILABLE_UPON_REQUEST"),
     *                  @OA\Property(property="label", type="string", example="Available upon request"),
     *                  @OA\Property(property="valid_since", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="valid_until", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="definition", type="string", example="Access to the software ..."),
     *                  @OA\Property(property="origin", type="string", example="HDR"),
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
     *                  @OA\Property(property="code", type="string", example="HDR_CATEGORY_AVAILABLE_UPON_REQUEST"),
     *                  @OA\Property(property="label", type="string", example="Available upon request"),
     *                  @OA\Property(property="valid_since", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="valid_until", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="definition", type="string", example="Access to the software ..."),
     *                  @OA\Property(property="verified", type="boolean", example="1"),
     *                  @OA\Property(property="origin", type="string", example="HDR"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
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
    public function update(UpdateLicense $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            License::where('id', $id)->update([
                'code' => strtoupper($input['code']),
                'label' => $input['label'],
                'valid_since' => $input['valid_since'],
                'valid_until' => array_key_exists('valid_until', $input) ? $input['valid_until'] : null,
                'definition' => $input['definition'],
                'origin' => $input['origin'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'License ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => License::where('id', $id)->first()
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
     *      path="/api/v1/licenses/{id}",
     *      summary="Edit a tool license",
     *      description="Edit a tool license",
     *      tags={"License"},
     *      summary="License@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="license id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="license id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Category definition",
     *          @OA\JsonContent(
     *              required={"code", "label", "valid_since", "definition", "origin"},
     *                  @OA\Property(property="code", type="string", example="HDR_CATEGORY_AVAILABLE_UPON_REQUEST"),
     *                  @OA\Property(property="label", type="string", example="Available upon request"),
     *                  @OA\Property(property="valid_since", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="valid_until", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="definition", type="string", example="Access to the software ..."),
     *                  @OA\Property(property="origin", type="string", example="HDR"),
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
     *                  @OA\Property(property="code", type="string", example="HDR_CATEGORY_AVAILABLE_UPON_REQUEST"),
     *                  @OA\Property(property="label", type="string", example="Available upon request"),
     *                  @OA\Property(property="valid_since", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="valid_until", type="datetime", example="2024-04-15 00:00:00"),
     *                  @OA\Property(property="definition", type="string", example="Access to the software ..."),
     *                  @OA\Property(property="verified", type="boolean", example="1"),
     *                  @OA\Property(property="origin", type="string", example="HDR"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
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
    public function edit(EditLicense $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $arrayKeys = [
                'code',
                'label',
                'valid_since',
                'valid_until',
                'definition',
                'origin',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            License::where('id', $id)->update($array);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EDIT',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'License ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => License::where('id', $id)->first(),
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
     *      path="/api/v1/licenses/{id}",
     *      summary="Delete a License",
     *      description="Delete a License",
     *      tags={"License"},
     *      summary="License@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="License id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="License id",
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
    public function destroy(DeleteLicense $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            License::where(['id' => $id])->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'License ' . $id . ' deleted',
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
