<?php

namespace App\Http\Controllers\Api\V1;

use Exception;

use App\Models\Dataset;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{

    /**
     * @OA\Get(
     *      path="/api/v1/search/datasets",
     *      summary="Keyword search across gateway datasets",
     *      description="Returns gateway datasets related to the provided query term(s)",
     *      tags={"Search-Datasets"},
     *      summary="Search@datasets",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="asthma dataset"),
     *              )
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Field to sort by (default: 'score')",
     *          example="created",
     *          @OA\Schema(
     *              type="string",
     *              description="Field to sort by (score, created_at, title)",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="direction",
     *          in="query",
     *          description="Sort direction ('asc' or 'desc', default: 'desc')",
     *          example="desc",
     *          @OA\Schema(
     *              type="string",
     *              enum={"asc", "desc"},
     *              description="Sort direction",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="_source", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="abstract", type="string"),
     *                              @OA\Property(property="description", type="string"),
     *                              @OA\Property(property="keywords", type="string"),
     *                              @OA\Property(property="named_entities", type="array", @OA\Items()),
     *                              @OA\Property(property="publisherName", type="string"),
     *                              @OA\Property(property="shortTitle", type="string"),
     *                              @OA\Property(property="title", type="string"),
     *                              @OA\Property(property="created_at", type="string")
     *                          )
     *                      ),
     *                      @OA\Property(property="highlight", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="abstract", type="array", @OA\Items()),
     *                              @OA\Property(property="description", type="array", @OA\Items())
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function datasets(Request $request): JsonResponse
    {
        try {
            $sort = $request->query('sort',"score:desc");   
        
            $tmp = explode(":", $sort);
            $sortInput = $tmp[0];
            $sortField = ($sortInput === 'title') ? 'shortTitle' : $sortInput;
            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $urlString = env('SEARCH_SERVICE_URL') . '/search/datasets';

            $response = Http::withBody(
                $request->getContent(), 'application/json'
            )->get($urlString);

            $datasetsArray = $response['hits']['hits'];
            // join to created at from DB
            foreach (array_values($datasetsArray) as $i => $d) {
                $datasetModel = Dataset::where(['id' => $d['_id']])->first();
                if ($datasetModel) {
                    $datasetsArray[$i]['_source']['created_at'] = $datasetModel->toArray()['created_at'];
                }
            }

            if ($sortField === 'score') {
                $datasetsArraySorted = $sortDirection === 'desc' ? $datasetsArray : array_reverse($datasetsArray);
                return response()->json([
                    'message' => 'success',
                    'data' => $datasetsArraySorted->paginate(2),
                ], 200);
            }

            if ($sortDirection === 'asc') { 
                usort(
                    $datasetsArray, 
                    function($a, $b) use ($sortField) {
                        return $a['_source'][$sortField] <=> $b['_source'][$sortField];
                    }
                );
            } else {
                usort(
                    $datasetsArray, 
                    function($a, $b) use ($sortField) {
                        return -1 * ($a['_source'][$sortField] <=> $b['_source'][$sortField]);
                    }
                );
            }

            return response()->json([
                'message' => 'success',
                'data' => $datasetsArray,
            ], 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
