<?php

namespace App\Http\Controllers\Api\V2;

use Auditor;
use Config;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\NightlyDatasetTest;
use Illuminate\Http\JsonResponse;

class NightlyDatasetTestController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/v2/nightly_dataset_tests",
     *    operationId="fetch_nightly_dataset_tests_v2",
     *    tags={"NightlyDatasetTests"},
     *    summary="NightlyDatasetTestController@index",
     *    description="Get the results of the nightly dataset reachability check, with a summary and a list of failures",
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
            // Null status_code means the request never got a response (e.g. a local
            // connection blip) rather than the dataset page actually erroring, so we
            // exclude those from the metrics entirely and only count real HTTP errors.
            $results = NightlyDatasetTest::whereNotNull('status_code')->get();

            $totalChecked = $results->count();
            $successful = $results->filter(fn ($result) => $result->isSuccessful());
            $failed = $results->reject(fn ($result) => $result->isSuccessful());

            $failedDatasets = $failed->map(function ($result) {
                return [
                    'datasetId' => $result->dataset_id,
                    'statusCode' => $result->status_code,
                    'checkedAt' => $result->updated_at,
                ];
            })->values();

            $totalFailed = $failed->count();

            $data = [
                'summary' => [
                    'totalChecked' => $totalChecked,
                    'totalSuccessful' => $successful->count(),
                    'totalFailed' => $totalFailed,
                    'percentageFailed' => $totalChecked > 0
                        ? round(($totalFailed / $totalChecked) * 100, 1)
                        : 0,
                ],
                'failedDatasets' => $failedDatasets,
            ];

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $data,
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
