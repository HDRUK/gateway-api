<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use MetadataManagementController as MMC;

use App\Models\DatasetVersion;
use App\Models\Collection;
use App\Models\Dataset;
use App\Models\Tool;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{

    /**
     * @OA\Post(
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
     *          in="body",
     *          description="Field to sort by (default: 'score')",
     *          example="created",
     *          @OA\Schema(
     *              type="string",
     *              description="Field to sort by (score, created_at, title)",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="direction",
     *          in="body",
     *          description="Sort direction ('asc' or 'desc', default: 'desc')",
     *          example="desc",
     *          @OA\Schema(
     *              type="string",
     *              enum={"asc", "desc"},
     *              description="Sort direction",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          in="body",
     *          description="Filters to apply to this search term",
     *          example="dataset",
     *          @OA\Schema(
     *              type="string"
     *              description="Filters to apply to this search term",
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

            $filters = (isset($request['filters']) ? $request['filters'] : []);

            $response = Http::withBody(
                $request->getContent(), 'application/json'
            )->get($urlString);

            $datasetsArray = $response['hits']['hits'];
            $matchedIds = [];
            // join to created at from DB
            foreach (array_values($datasetsArray) as $i => $d) {
               $matchedIds[] = $d['_id'];
            }

            // debug code left in to map to dataset_version Ids for testing - TODO Remove
            // $matchedIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

            // whereRaw(1=1) here is a trick to allow us to access the builder model
            // without fully forming a query first
            $datasetsFiltered = DatasetVersion::whereRaw('1=1');

            // Apply any filters to retrieve matching datasets on like basis, for
            // later intersection with elastic matched datasets
            foreach ($filters as $filter => $value) {
                foreach ($value as $key => $val) {
                    MMC::applySearchFilter($datasetsFiltered, $filter, $key, $val['terms']);
                }
            }

            $ds = $datasetsFiltered->whereIn('dataset_id', $matchedIds)->get();
            $likeIds = [];
            foreach ($ds as $d) {
                $likeIds[] = $d['dataset_id'];
            }

            $slimSet = array_intersect($matchedIds, $likeIds);

            $datasetsModels = Dataset::with('versions')->whereIn('id', $slimSet)->get()->toArray();
            foreach ($datasetsArray as $i => $dataset) {
                if (!in_array($dataset['_id'], $slimSet)) {
                    unset($datasetsArray[$i]);
                    continue;
                }
                foreach ($datasetsModels as $model) {
                    if ((int) $dataset['_id'] === $model['id']) {
                        $datasetsArray[$i]['_source']['created_at'] = $model['versions'][0]['created_at'];
                        $datasetsArray[$i]['metadata'] = $model['versions'][0]['metadata'];
                    }
                }
            }

            $datasetsArraySorted = $this->sortSearchResult($datasetsArray, $sortField, $sortDirection);

            return response()->json([
                'message' => 'success',
                'data' => $datasetsArraySorted,
            ], 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/search/tools",
     *      summary="Keyword search across gateway tools",
     *      description="Returns gateway tools related to the provided query term(s)",
     *      tags={"Search-Tools"},
     *      summary="Search@tools",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="nlp tools"),
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
     *              description="Field to sort by (score, created_at, name)",
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
     *                              @OA\Property(property="name", type="string"),
     *                              @OA\Property(property="description", type="string"),
     *                              @OA\Property(property="category", type="string"),
     *                              @OA\Property(property="tags", type="array", @OA\Items())
     *                          )
     *                      ),
     *                      @OA\Property(property="highlight", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="description", type="array", @OA\Items())
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function tools(Request $request): JsonResponse
    {
        try {
            $sort = $request->query('sort',"score:desc");   
        
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $urlString = env('SEARCH_SERVICE_URL') . '/search/tools';

            $response = Http::withBody(
                $request->getContent(), 'application/json'
            )->get($urlString);

            $toolsArray = $response['hits']['hits'];
            // join to created at from DB
            foreach (array_values($toolsArray) as $i => $d) {
                $toolModel = Tool::where(['id' => $d['_id']])->first();
                $toolsArray[$i]['_source']['created_at'] = $toolModel->toArray()['created_at'];
            }

            $toolsArraySorted = $this->sortSearchResult($toolsArray, $sortField, $sortDirection);

            return response()->json([
                'message' => 'success',
                'data' => $toolsArraySorted,
            ], 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/search/collections",
     *      summary="Keyword search across gateway collections",
     *      description="Returns gateway collections related to the provided query term(s)",
     *      tags={"Search-Collections"},
     *      summary="Search@collections",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="SDE collections"),
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
     *              description="Field to sort by (score, created_at, name)",
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
     *                              @OA\Property(property="name", type="string"),
     *                              @OA\Property(property="description", type="string"),
     *                              @OA\Property(property="keywords", type="string"),
     *                              @OA\Property(property="relatedObjects.keywords", type="string"),
     *                              @OA\Property(property="relatedObjects.title", type="string"),
     *                              @OA\Property(property="relatedObjects.name", type="string"),
     *                              @OA\Property(property="relatedObjects.description", type="string")
     *                          )
     *                      ),
     *                      @OA\Property(property="highlight", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="description", type="array", @OA\Items())
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function collections(Request $request): JsonResponse
    {
        try {
            $sort = $request->query('sort',"score:desc");   
        
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $urlString = env('SEARCH_SERVICE_URL') . '/search/collections';

            $response = Http::withBody(
                $request->getContent(), 'application/json'
            )->get($urlString);

            $collectionsArray = $response['hits']['hits'];
            // join to created at from DB
            foreach (array_values($collectionsArray) as $i => $d) {
                $collectionModel = Collection::where(['id' => $d['_id']])->first();
                $collectionsArray[$i]['_source']['created_at'] = $collectionModel->toArray()['created_at'];
            }

            $collectionsArraySorted = $this->sortSearchResult($collectionsArray, $sortField, $sortDirection);

            return response()->json([
                'message' => 'success',
                'data' => $collectionsArraySorted,
            ], 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * Sorts results returned by the search service according to sort field and direction
     * 
     * @param array $resultArray The results from the keyword search
     * @param string $sortField The field to sort result by (e.g. name, created_at etc.)
     * @param string $sortDirection The direction to sort the result (i.e. asc, desc)
     * 
     * @return array
     */
    private function sortSearchResult(array $resultArray, string $sortField, string $sortDirection): array
    {
        if ($sortField === 'score') {
            $resultArraySorted = $sortDirection === 'desc' ? $resultArray : array_reverse($resultArray);
            return $resultArraySorted;
        }

        if ($sortDirection === 'asc') { 
            usort(
                $resultArray, 
                function($a, $b) use ($sortField) {
                    return $a['_source'][$sortField] <=> $b['_source'][$sortField];
                }
            );
        } else {
            usort(
                $resultArray, 
                function($a, $b) use ($sortField) {
                    return -1 * ($a['_source'][$sortField] <=> $b['_source'][$sortField]);
                }
            );
        }
        return $resultArray;
    }

}
