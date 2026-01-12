<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Models\Workgroup;
use Auditor;
use Exception;
use Illuminate\Http\JsonResponse;

class WorkgroupController extends Controller
{
    use RequestTransformation;

    /**
     * constructor method
     */
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/workgroups",
     *    operationId="fetch_all_workgroups",
     *    tags={"Workgroups"},
     *    summary="WorkgroupController@index",
     *    description="Get All Workgroups",
     *    security={{"bearerAuth":{}}},
     *
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]"
     *       ),
     *    ),
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $workgroups = Workgroup::all();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Workgroups get all',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $workgroups,
            ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
