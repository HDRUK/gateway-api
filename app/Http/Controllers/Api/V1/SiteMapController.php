<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Collection;
use App\Models\DataProviderColl;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Team;
use App\Models\Tool;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class SiteMapController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/v1/sitemap",
     *    operationId="fetch_all_sitemap",
     *    tags={"Application"},
     *    summary="SiteMapController@index",
     *    description="Returns a list of all ids and last updated date for Collections, Data Custodians, Data Custodian Networks, Durs, DataSets, Tools",
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="collections", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *                   @OA\Property(property="dataCustodians", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *                   @OA\Property(property="dataCustodianNetworks", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *                   @OA\Property(property="dataSets", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *                   @OA\Property(property="durs", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *                   @OA\Property(property="tools", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *                )
     *             )
     *          )
     *       )
     *    )
     * )
     */
    public function index(): JsonResponse
    {
        $allCollections = Collection::select('id', 'updated_at')->get();
        $allDataCustodians = Team::select('id', 'updated_at')->get();
        $allDataCustodianNetworks = DataProviderColl::select('id', 'updated_at')->get();
        $allDurs = Dur::select('id', 'updated_at')->get();
        $allDatasets = Dataset::select('id', 'updated_at')->get();
        $allTools = Tool::select('id', 'updated_at')->get();


        return response()->json(
            [
                'collections' => $allCollections,
                'dataCustodians' => $allDataCustodians,
                'dataCustodianNetworks' => $allDataCustodianNetworks,
                'dataSets' => $allDatasets,
                'durs' => $allDurs,
                'tools' => $allTools,
            ]
        );
    }

}
