<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Carbon\Carbon;

use App\Models\AuditLog;
use App\Http\Requests\AuditLogRequest;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/audit_logs",
     *      summary="List of system audit logs",
     *      description="Returns a list of audit logs",
     *      tags={"AuditLog"},
     *      summary="AuditLog@index",
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
     *                      @OA\Property(property="user_id", type="integer", example="100"),
     *                      @OA\Property(property="description", type="string", example="someType"),
     *                      @OA\Property(property="function", type="string", example="some value"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $logs = AuditLog::all();
        return response()->json([
            'data' => $logs,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v1/audit_logs/{id}",
     *      summary="Return a single system audit log",
     *      description="Return a single system audit log",
     *      tags={"AuditLog"},
     *      summary="AuditLog@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="user_id", type="integer", example="100"),
     *                  @OA\Property(property="description", type="string", example="someType"),
     *                  @OA\Property(property="function", type="string", example="some value"),
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
    public function show(Request $request, int $id)
    {
        $logs = AuditLog::findOrFail($id);
        if ($logs) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $logs,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    /**
     * @OA\Post(
     *      path="/api/v1/audit_logs",
     *      summary="Create a new system audit log",
     *      description="Creates a new system audit log",
     *      tags={"AuditLog"},
     *      summary="AuditLog@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Filter definition",
     *          @OA\JsonContent(
     *              required={"user_id", "description", "function"},
     *              @OA\Property(property="user_id", type="integer", example="100"),
     *              @OA\Property(property="description", type="string", example="someType"),
     *              @OA\Property(property="function", type="string", example="some value"),
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
    public function store(AuditLogRequest $request)
    {
        try {
            $logs = AuditLog::create($request->post());
            if ($logs) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                    'data' => $logs->id,
                ], Config::get('statuscodes.STATUS_CREATED.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
            ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/audit_logs/{id}",
     *      summary="Update a system audit log",
     *      description="Update a system audit log",
     *      tags={"AuditLog"},
     *      summary="AuditLog@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Audit log definition",
     *          @OA\JsonContent(
     *              required={"user_id", "description", "function"},
     *              @OA\Property(property="user_id", type="integer", example="100"),
     *              @OA\Property(property="description", type="string", example="someType"),
     *              @OA\Property(property="function", type="string", example="some value"),
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
     *                  @OA\Property(property="user_id", type="integer", example="100"),
     *                  @OA\Property(property="description", type="string", example="someType"),
     *                  @OA\Property(property="function", type="string", example="some value"),
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
    public function update(AuditLogRequest $request, int $id)
    {
        try {
            $log = AuditLog::findOrFail($id);
            $body = $request->post();

            $log->updated_at = Carbon::now();
            $log->user_id = $body['user_id'];
            $log->description = $body['description'];
            $log->function = $body['function'];

            if ($log->save()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $log,
                ], Config::get('statuscodes.STATUS_OK.code'));
            } else {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
                ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/audit_logs/{id}",
     *      summary="Delete a system audit log",
     *      description="Delete a system audit log",
     *      tags={"AuditLog"},
     *      summary="AuditLog@destroy",
     *      security={{"bearerAuth":{}}},
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
    public function destroy(Request $request, int $id)
    {
        $log = AuditLog::findOrFail($id);
        if ($log) {
            $log->deleted_at = Carbon::now();
            if ($log->save()) {
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
    }
}
