<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use App\Models\DarIntegration;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\IntegrationOverride;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\DarIntegration\GetDARIntegration;
use App\Http\Requests\DarIntegration\EditDARIntegration;
use App\Http\Requests\DarIntegration\CreateDARIntegration;
use App\Http\Requests\DarIntegration\DeleteDARIntegration;
use App\Http\Requests\DarIntegration\UpdateDARIntegration;

class DarIntegrationController extends Controller
{
    use RequestTransformation, IntegrationOverride;
    
    /**
     * @OA\Get(
     *      path="/api/v1/dar-integration",
     *      summary="List of system Dar Integrations",
     *      description="Returns a list of DAR integrations enabled on the system",
     *      tags={"DarIntegration"},
     *      summary="DarIntegration@index",
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
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                      @OA\Property(property="notification_email", type="string", example="someone@somewhere.com"),
     *                      @OA\Property(property="outbound_auth_type", type="string", example=""),
     *                      @OA\Property(property="outbound_auth_key", type="string", example=""),
     *                      @OA\Property(property="outbound_endpoints_base_url", type="string", example=""),
     *                      @OA\Property(property="outbound_endpoints_enquiry", type="string", example=""),
     *                      @OA\Property(property="outbound_endpoints_5safes", type="string", example=""),
     *                      @OA\Property(property="outbound_endpoints_5safes_files", type="string", example=""),
     *                      @OA\Property(property="inbound_service_account_id", type="string", example="")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());
    
            $dars = DarIntegration::where('enabled', 1)->paginate(Config::get('constants.per_page'), ['*'], 'page');

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ? $applicationOverrideDefaultValues['team_id'] : $input['team_id']),    
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "DAR get all",
            ]);

            return response()->json(
                $dars
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/dar-integration/{id}",
     *      summary="Return a single system Dar Integration",
     *      description="Returns a single DAR integration enabled on the system",
     *      tags={"DarIntegration"},
     *      summary="DarIntegration@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="dar integration id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="dar integration id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example="123"),
     *              @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="enabled", type="boolean", example="1"),
     *              @OA\Property(property="notification_email", type="string", example="someone@somewhere.com"),
     *              @OA\Property(property="outbound_auth_type", type="string", example=""),
     *              @OA\Property(property="outbound_auth_key", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_base_url", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_enquiry", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_5safes", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_5safes_files", type="string", example=""),
     *              @OA\Property(property="inbound_service_account_id", type="string", example="")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
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
    public function show(GetDARIntegration $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());
    
            $dar = DarIntegration::findOrFail($id);
            if ($dar) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $dar,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }
    
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ? $applicationOverrideDefaultValues['team_id'] : $input['team_id']),    
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "DAR get " . $id,
            ]);
    
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/dar-integration/{id}",
     *      summary="Create a new system Dar Integration",
     *      description="Creates a new DAR integration enabled on the system",
     *      tags={"DarIntegration"},
     *      summary="DarIntegration@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="DarIntegration definition",
     *          @OA\JsonContent(
     *              required={"enabled", "notification_email", "outbound_auth_type", "outbound_auth_key",
     *                  "outbound_endpoints_base_url", "outbound_endpoints_enquiry", "outbound_endpoints_5safes",
     *                  "outbound_endpoints_5safes_files", "inbound_service_account_id"},
     *              @OA\Property(property="enabled", type="integer", example="1"),
     *              @OA\Property(property="notification_email", type="string", example="someone@somewhere.com"),
     *              @OA\Property(property="outbound_auth_type", type="string", example=""),
     *              @OA\Property(property="outbound_auth_key", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_base_url", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_enquiry", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_5safes", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_5safes_files", type="string", example=""),
     *              @OA\Property(property="inbound_service_account_id", type="string", example=""),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Created",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="integer", example="100")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function store(CreateDARIntegration $request): JsonResponse
    {
        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $dar = DarIntegration::create([
                'enabled' => $input['enabled'],
                'notification_email' => $input['notification_email'],
                'outbound_auth_type' => $input['outbound_auth_type'],
                'outbound_auth_key' => $input['outbound_auth_key'],
                'outbound_endpoints_base_url' => $input['outbound_endpoints_base_url'],
                'outbound_endpoints_enquiry' => $input['outbound_endpoints_enquiry'],
                'outbound_endpoints_5safes' => $input['outbound_endpoints_5safes'],
                'outbound_endpoints_5safes_files' => $input['outbound_endpoints_5safes_files'],
                'inbound_service_account_id' => $input['inbound_service_account_id'],
            ]);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ? $applicationOverrideDefaultValues['team_id'] : $input['team_id']),    
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "DAR " . $dar->id . " created",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $dar->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/dar-integration/{id}",
     *      summary="Updates a system Dar Integration",
     *      description="Updates a DAR integration enabled on the system",
     *      tags={"DarIntegration"},
     *      summary="DarIntegration@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="dar integration id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="dar integration id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DarIntegration definition",
     *          @OA\JsonContent(
     *              required={"enabled", "notification_email", "outbound_auth_type", "outbound_auth_key",
     *                  "outbound_endpoints_base_url", "outbound_endpoints_enquiry", "outbound_endpoints_5safes",
     *                  "outbound_endpoints_5safes_files", "inbound_service_account_id"},
     *              @OA\Property(property="enabled", type="integer", example="1"),
     *              @OA\Property(property="notification_email", type="string", example="someone@somewhere.com"),
     *              @OA\Property(property="outbound_auth_type", type="string", example=""),
     *              @OA\Property(property="outbound_auth_key", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_base_url", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_enquiry", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_5safes", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_5safes_files", type="string", example=""),
     *              @OA\Property(property="inbound_service_account_id", type="string", example="")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="notification_email", type="string", example="someone@somewhere.com"),
     *                  @OA\Property(property="outbound_auth_type", type="string", example=""),
     *                  @OA\Property(property="outbound_auth_key", type="string", example=""),
     *                  @OA\Property(property="outbound_endpoints_base_url", type="string", example=""),
     *                  @OA\Property(property="outbound_endpoints_enquiry", type="string", example=""),
     *                  @OA\Property(property="outbound_endpoints_5safes", type="string", example=""),
     *                  @OA\Property(property="outbound_endpoints_5safes_files", type="string", example=""),
     *                  @OA\Property(property="inbound_service_account_id", type="string", example="")
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function update(UpdateDARIntegration $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            DarIntegration::where('id', $id)->update([
                'enabled' => $input['enabled'],
                'notification_email' => $input['notification_email'],
                'outbound_auth_type' => $input['outbound_auth_type'],
                'outbound_auth_key' => $input['outbound_auth_key'],
                'outbound_endpoints_base_url' => $input['outbound_endpoints_base_url'],
                'outbound_endpoints_enquiry' => $input['outbound_endpoints_enquiry'],
                'outbound_endpoints_5safes' => $input['outbound_endpoints_5safes'],
                'outbound_endpoints_5safes_files' => $input['outbound_endpoints_5safes_files'],
                'inbound_service_account_id' => $input['inbound_service_account_id'],
            ]);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ? $applicationOverrideDefaultValues['team_id'] : $input['team_id']),    
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "DAR " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DarIntegration::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/dar-integration/{id}",
     *      summary="Edit a system Dar Integration",
     *      description="Edit a DAR integration enabled on the system",
     *      tags={"DarIntegration"},
     *      summary="DarIntegration@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="dar integration id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="dar integration id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DarIntegration definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="enabled", type="integer", example="1"),
     *              @OA\Property(property="notification_email", type="string", example="someone@somewhere.com"),
     *              @OA\Property(property="outbound_auth_type", type="string", example=""),
     *              @OA\Property(property="outbound_auth_key", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_base_url", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_enquiry", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_5safes", type="string", example=""),
     *              @OA\Property(property="outbound_endpoints_5safes_files", type="string", example=""),
     *              @OA\Property(property="inbound_service_account_id", type="string", example="")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="notification_email", type="string", example="someone@somewhere.com"),
     *                  @OA\Property(property="outbound_auth_type", type="string", example=""),
     *                  @OA\Property(property="outbound_auth_key", type="string", example=""),
     *                  @OA\Property(property="outbound_endpoints_base_url", type="string", example=""),
     *                  @OA\Property(property="outbound_endpoints_enquiry", type="string", example=""),
     *                  @OA\Property(property="outbound_endpoints_5safes", type="string", example=""),
     *                  @OA\Property(property="outbound_endpoints_5safes_files", type="string", example=""),
     *                  @OA\Property(property="inbound_service_account_id", type="string", example="")
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function edit(EditDARIntegration $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());
            $arrayKeys = [
                'enabled', 
                'notification_email', 
                'outbound_auth_type', 
                'outbound_auth_key', 
                'outbound_endpoints_base_url', 
                'outbound_endpoints_enquiry', 
                'outbound_endpoints_5safes',
                'outbound_endpoints_5safes_files',
                'inbound_service_account_id',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            DarIntegration::where('id', $id)->update($array);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ? $applicationOverrideDefaultValues['team_id'] : $input['team_id']),    
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "DAR " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DarIntegration::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/dar-integrations/{id}",
     *      summary="Delete a system Dar Integration",
     *      description="Delete a system Dar Integration",
     *      tags={"DarIntegration"},
     *      summary="DarIntegration@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="dar integration id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="dar integration id",
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
    public function destroy(DeleteDARIntegration $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $dar = DarIntegration::findOrFail($id);
            if ($dar) {
                $dar->delete();

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ? $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ? $applicationOverrideDefaultValues['team_id'] : $input['team_id']),    
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "DAR " . $id . " deleted",
            ]);

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
