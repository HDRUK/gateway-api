<?php

namespace App\Http\Controllers\Api\V2;

use Auditor;
use Config;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\DatasetLinkCheckResult;
use Illuminate\Http\JsonResponse;

class DatasetLinkCheckResultController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/v2/dataset_link_check_results",
     *    operationId="fetch_dataset_link_check_results_v2",
     *    tags={"DatasetLinkCheckResults"},
     *    summary="DatasetLinkCheckResultController@index",
     *    description="Get the confirmed dead links (HTTP 404, verified across multiple checks) found in active dataset metadata by the nightly link check",
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $results = DatasetLinkCheckResult::orderBy('team_name')
                ->orderBy('dataset_id')
                ->get()
                ->map(fn ($result) => [
                    'teamId' => $result->team_id,
                    'teamName' => $result->team_name,
                    'datasetId' => $result->dataset_id,
                    'url' => $result->url,
                    'statusCode' => $result->status_code,
                    'checkedAt' => $result->updated_at,
                ])
                ->values();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $results,
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
}
