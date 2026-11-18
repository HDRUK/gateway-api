<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SimilarDatasets;
use App\SearchProviders\HDRUK;
use Auditor;
use Illuminate\Http\JsonResponse;

class SimilarDatasetsController extends Controller
{
    private const SIMILAR_LIMIT = 3;

    /**
     * @OA\Post(
     *      path="/api/v2/search/similar/datasets",
     *      operationId="search_similar_datasets_v2",
     *      summary="Search for similar datasets",
     *      description="Returns up to three gateway datasets sharing the most terms with the provided dataset's keywords and title. The provided dataset is never included.",
     *      tags={"Search-Similar-Datasets"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  required={"id"},
     *                  @OA\Property(property="id", type="integer", example=1, description="Dataset id")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success; empty data when the dataset is not indexed",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="_id", type="string"),
     *                      @OA\Property(property="_source", type="object"),
     *                      @OA\Property(property="metadata", type="object")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=400, description="Missing or invalid dataset id")
     * )
     *
     * @OA\Post(
     *      path="/api/v1/search/similar/datasets",
     *      operationId="search_similar_datasets",
     *      summary="Search for similar datasets (deprecated)",
     *      description="Deprecated: use /api/v2/search/similar/datasets, which this endpoint now serves. Responses carry Deprecation, Sunset and Link headers; it stops working after the Sunset date (17 May 2027).",
     *      tags={"Search-Similar-Datasets"},
     *      deprecated=true,
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  required={"id"},
     *                  @OA\Property(property="id", type="integer", example=1, description="Dataset id")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Same as /api/v2/search/similar/datasets"),
     *      @OA\Response(response=400, description="Missing or invalid dataset id")
     * )
     */
    public function search(SimilarDatasets $request): JsonResponse
    {
        $datasets = app(HDRUK::class)->similarDatasets($request->integer('id'), self::SIMILAR_LIMIT);

        Auditor::log([
            'action_type' => 'POST',
            'action_name' => class_basename($this) . '@' . __FUNCTION__,
            'description' => 'Search similar datasets',
        ]);

        return response()->json(['data' => $datasets]);
    }
}
