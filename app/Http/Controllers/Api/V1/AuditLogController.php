<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
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

class AuditLogController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *    path="/api/v1/audit_logs",
     *    summary="List of system audit logs",
     *    description="Returns a list of audit logs",
     *    tags={"AuditLog"},
     *    summary="AuditLog@index",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="data", type="array",
     *          @OA\Items(
     *             @OA\Property(property="id", type="integer", example="123"),
     *             @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="deleted_at", type="datetime", example="null"),
     *             @OA\Property(property="user_id", type="integer", example="100"),
     *             @OA\Property(property="team_id", type="integer", example="100"),
     *             @OA\Property(property="action_type", type="string", example="UPDATE"),
     *             @OA\Property(property="action_name", type="string", example="Gateway API"),
     *             @OA\Property(property="description", type="string", example="beatae praesentium ut consequatur at ipsam facilis sit neque ut"),
     *          )
     *       ),
     *       @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/audit_logs?page=1"),
     *       @OA\Property(property="from", type="integer", example="1"),
     *       @OA\Property(property="last_page", type="integer", example="1"),
     *       @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/audit_logs?page=1"),
     *       @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *       @OA\Property(property="next_page_url", type="string", example="null"),
     *       @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/audit_logs"),
     *       @OA\Property(property="per_page", type="integer", example="25"),
     *       @OA\Property(property="prev_page_url", type="string", example="null"),
     *       @OA\Property(property="to", type="integer", example="3"),
     *       @OA\Property(property="total", type="integer", example="3"),
     *    )
     *   )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $logs = AuditLog::paginate(Config::get('constants.per_page'), ['*'], 'page');
            return response()->json(
                $logs,
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="null"),
     *                      @OA\Property(property="user_id", type="integer", example="100"),
     *                      @OA\Property(property="team_id", type="integer", example="100"),
     *                      @OA\Property(property="action_type", type="string", example="UPDATE"),
     *                      @OA\Property(property="action_name", type="string", example="Gateway API"),
     *                      @OA\Property(property="description", type="string", example="beatae praesentium ut consequatur at ipsam facilis sit neque ut"),
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
        try {
            $logs = AuditLog::findOrFail($id);
            if ($logs) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $logs,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
     *              required={"action_name", "description"},
     *              @OA\Property(property="user_id", type="integer", example="100"),
     *              @OA\Property(property="team_id", type="integer", example="100"),
     *              @OA\Property(property="action_type", type="string", example="UPDATE"),
     *              @OA\Property(property="action_name", type="string", example="Gateway API"),
     *              @OA\Property(property="description", type="string", example="beatae praesentium ut consequatur at ipsam facilis sit neque ut"),
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
            $input = $request->all();
            $arrayKeys = [
                'user_id',
                'team_id',
                'action_type',
                'action_name',
                'description',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            $logs = AuditLog::create($array);
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $logs->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));

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
     *              required={"action_name", "description"},
     *              @OA\Property(property="user_id", type="integer", example="100"),
     *              @OA\Property(property="team_id", type="integer", example="100"),
     *              @OA\Property(property="action_type", type="string", example="UPDATE"),
     *              @OA\Property(property="action_name", type="string", example="Gateway API"),
     *              @OA\Property(property="description", type="string", example="beatae praesentium ut consequatur at ipsam facilis sit neque ut"),
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
     *                  @OA\Property(property="team_id", type="integer", example="100"),
     *                  @OA\Property(property="action_type", type="string", example="UPDATE"),
     *                  @OA\Property(property="action_name", type="string", example="Gateway API"),
     *                  @OA\Property(property="description", type="string", example="beatae praesentium ut consequatur at ipsam facilis sit neque ut"),
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
            $input = $request->all();
            $arrayKeys = [
                'user_id',
                'team_id',
                'action_type',
                'action_name',
                'description',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            AuditLog::where('id', $id)->update($array);
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => AuditLog::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
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
     *              @OA\Property(property="team_id", type="integer", example="100"),
     *              @OA\Property(property="action_type", type="string", example="UPDATE"),
     *              @OA\Property(property="action_name", type="string", example="Gateway API"),
     *              @OA\Property(property="description", type="string", example="beatae praesentium ut consequatur at ipsam facilis sit neque ut"),
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
     *                  @OA\Property(property="team_id", type="integer", example="100"),
     *                  @OA\Property(property="action_type", type="string", example="UPDATE"),
     *                  @OA\Property(property="action_name", type="string", example="Gateway API"),
     *                  @OA\Property(property="description", type="string", example="beatae praesentium ut consequatur at ipsam facilis sit neque ut"),
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
                'team_id',
                'action_type',
                'action_name',
                'description',
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
        try {
            AuditLog::where('id', $id)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
