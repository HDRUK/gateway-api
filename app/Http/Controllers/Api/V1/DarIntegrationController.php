<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Throwable;
use App\Models\DarIntegration;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\DarIntegrationRequest;

class DarIntegrationController extends Controller
{
    /**
     * @OA\Get(
     *      path="api/v1/dar-integration",
     *      summary="List of system Dar Integrations",
     *      description="Returns a list of DAR integrations enabled on the system",
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
    public function index(Request $request)
    {
        $dars = DarIntegration::where('enabled', 1)->get();
        return response()->json([
            'message' => Config::get('statuscodes.STATUS_OK.message'),
            'data' => $dars
        ], Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * @OA\Get(
     *      path="api/v1/dar-integration/{id}",
     *      summary="Return a single system Dar Integration",
     *      description="Returns a single DAR integration enabled on the system",
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
    public function show(Request $request, int $id)
    {
        $dar = DarIntegration::findOrFail($id);
        if ($dar) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dar,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    /**
     * @OA\Post(
     *      path="api/v1/dar-integration/{id}",
     *      summary="Create a new system Dar Integration",
     *      description="Creates a new DAR integration enabled on the system",
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
    public function store(DarIntegrationRequest $request)
    {
        try {
            $dar = DarIntegration::create($request->all());
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $dar->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));

        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }

        $dar = DarIntegration::create($request->post());
        if ($dar) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dar->id,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
        ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
    }

    /**
     * @OA\Patch(
     *      path="api/v1/dar-integration/{id}",
     *      summary="Updates a system Dar Integration",
     *      description="Updates a DAR integration enabled on the system",
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
    public function update(DarIntegrationRequest $request, int $id): mixed
    {
        try {
            $dar = DarIntegration::find($id);
            if ($dar) {
                if ($dar->update($request->all()) === false) {
                    return response()->json([
                        'message' => Config::get('statuscodes.STATUS_BAD_REQUEST.message'),
                    ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
                }

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $dar,
                ], Config::get('statuscodes.STATUS_OK.code'));                
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="api/v1/dar-integrations/{id}",
     *      summary="Delete a system Dar Integration",
     *      description="Delete a system Dar Integration",
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
    public function destroy(Request $request, int $id): mixed
    {
        try {
            $dar = DarIntegration::find($id);
            $dar->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
