<?php

namespace App\Http\Controllers\Api\V2;

use Auditor;
use Config;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\DataProviderColl;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Publication;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class KeyMetricController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/v2/metrics",
     *    operationId="fetch_key_metrics_v2",
     *    tags={"Metrics"},
     *    summary="KeyMetricController@index",
     *    description="Get key metrics",
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
            $collections = Collection::where('status', Collection::STATUS_ACTIVE)->count();
            $custodianNetworks = DataProviderColl::where('enabled', 1)->count();
            $custodians = Team::whereHas('datasets', function ($query) {
                $query->where('status', Dataset::STATUS_ACTIVE);
            })->count();
            $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->count();
            $datasetGmi = Dataset::where([
                'status' => Dataset::STATUS_ACTIVE,
                'create_origin' => Dataset::ORIGIN_GMI,
            ])->count();
            $datasetCohortRequest = Dataset::where([
                'status' => Dataset::STATUS_ACTIVE,
                'is_cohort_discovery' => 1,
            ])->count();
            $durs = Dur::where('status', Dur::STATUS_ACTIVE)->count();
            $publications = Publication::where('status', Publication::STATUS_ACTIVE)->count();
            $tools = Tool::where('status', Tool::STATUS_ACTIVE)->count();
            $users = User::count();

            $metrics = [
                'collections' => $collections,
                'custodianNetworks' => $custodianNetworks,
                'custodians' => $custodians,
                'datasets' => $datasets,
                'datasetGmi' => $datasetGmi,
                'datasetCohortRequest' => $datasetCohortRequest,
                'durs' => $durs,
                'publications' => $publications,
                'tools' => $tools,
                'users' => $users,
            ];

            return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $metrics,
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
