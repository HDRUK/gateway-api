<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Carbon\Carbon;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\AuditLog\GetAuditLog;
use App\Http\Requests\AuditLog\EditAuditLog;
use App\Http\Requests\AuditLog\CreateAuditLog;
use App\Http\Requests\AuditLog\DeleteAuditLog;
use App\Http\Requests\AuditLog\UpdateAuditLog;
use App\Exceptions\InternalServerErrorException;

class AuditLogController extends Controller
{
    use RequestTransformation;

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
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::paginate(Config::get('constants.per_page'), ['*'], 'page');
        return response()->json(
            $logs,
        );
    }

    /**
     * @OA\Get(
     *      path="/api/v1/audit_logs/{id}",
     *      summary="Return a single system audit log",
     *      description="Return a single system audit log",
     *      tags={"AuditLog"},
     *      summary="AuditLog@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="audit log id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="audit log id",
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
    public function show(GetAuditLog $request, int $id): JsonResponse
    {
        $logs = AuditLog::findOrFail($id);
        if ($logs) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $logs,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        throw new NotFoundException();
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
    public function store(CreateAuditLog $request): JsonResponse
    {
        try {
            $logs = AuditLog::create($request->post());
            if ($logs) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                    'data' => $logs->id,
                ], Config::get('statuscodes.STATUS_CREATED.code'));
            }

            throw new InternalServerErrorException();
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
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="audit log id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="audit log id",
     *         ),
     *      ),
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
    public function update(UpdateAuditLog $request, int $id): JsonResponse
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
                throw new NotFoundException();
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/audit_logs/{id}",
     *      summary="Edit a system audit log",
     *      description="Edit a system audit log",
     *      tags={"AuditLog"},
     *      summary="AuditLog@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="audit log id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="audit log id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Audit log definition",
     *          @OA\JsonContent(
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
    public function edit(EditAuditLog $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $arrayKeys = [
                'user_id',
                'description',
                'function',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            AuditLog::where('id', $id)->update($array);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => AuditLog::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
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
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="audit log id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="audit log id",
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
    public function destroy(DeleteAuditLog $request, int $id): JsonResponse
    {
        $log = AuditLog::findOrFail($id);
        if ($log) {
            $log->deleted_at = Carbon::now();
            if ($log->save()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new InternalServerErrorException();
        }

        throw new NotFoundException();
    }
}
