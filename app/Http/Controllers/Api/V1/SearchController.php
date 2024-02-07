<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NotFoundException;
use Config;
use Exception;
use MetadataManagementController as MMC;

use App\Models\DatasetVersion;
use App\Models\Collection;
use App\Models\Dataset;
use App\Models\Tool;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Dur;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Facades\Http;

use App\Http\Traits\PaginateFromArray;

class SearchController extends Controller
{
    use PaginateFromArray;

    /**
     * @OA\Examples(
     *      example="filtersExample",
     *      summary="Example Filters payload",
     *      value={
     *          "filters": {
     *               "dataset": {
     *                   "publisherName": {
     *                      "terms": {
     *                          "BREATHE",
     *                          "HDRUK"
     *                      }
     *                   }
     *               }
     *           }
     *       }
     * ),
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
     *                  @OA\Property(property="sort", type="string", example="score"),
     *                  @OA\Property(property="direction", type="string", example="desc"),
     *                  @OA\Property(property="filters", type="string", example={"filtersExample": @OA\Schema(ref="#/components/examples/filtersExample")})
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example="1"),
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
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
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

            $perPage = request('perPage', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $datasetsArraySorted, $perPage);
            return response()->json($paginatedData, 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
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
     *              @OA\Property(property="current_page", type="integer", example="1"),
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
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
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

            $filters = (isset($request['filters']) ? $request['filters'] : []);
            $urlString = env('SEARCH_SERVICE_URL') . '/search/tools';

            $response = Http::withBody(
                $request->getContent(), 'application/json'
            )->get($urlString);

            $toolsArray = $response['hits']['hits'];

            $matchedIds = [];
            foreach (array_values($toolsArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $toolsFiltered = Tool::with("category");

            // Apply any filters to retrieve matching tools on like basis
            foreach ($filters as $filter => $value) {
                foreach ($value as $key => $val) {
                    MMC::applySearchFilter($toolsFiltered, $filter, $key, $val['terms']);
                }
            }

            //get all tools models that have been filtered and then matched by elastic
            $toolModels = $toolsFiltered->whereIn('id', $matchedIds)->get();

            $likeIds = [];
            foreach ($toolModels as $d) {
                $likeIds[] = $d['id'];
            }

            //IDs that have been matched and IDs that have been filtered
            $slimSet = array_intersect($matchedIds, $likeIds);

            foreach ($toolsArray as $i => $tool) {
                if (!in_array($tool['_id'], $slimSet)) {
                    unset($toolsArray[$i]);
                    continue;
                }
                foreach ($toolModels as $model){
                    if ((int) $tool['_id'] === $model['id']) {
                        $toolsArray[$i]['_source']['programmingLanguage'] = $model['tech_stack'];
                        $category = null;
                        if( $model->category){
                            $category = $model->category['name'];
                        }
                        $toolsArray[$i]['_source']['category'] = $category;
                        $toolsArray[$i]['_source']['created_at'] = $model['created_at'];
                        break;
                    }
                }
            }
     
            $toolsArraySorted = $this->sortSearchResult($toolsArray, $sortField, $sortDirection);

            $perPage = request('perPage', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $toolsArraySorted, $perPage);
            return response()->json($paginatedData, 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
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
     *              @OA\Property(property="current_page", type="integer", example="1"),
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
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
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

            $filters = (isset($request['filters']) ? $request['filters'] : []);
            $urlString = env('SEARCH_SERVICE_URL') . '/search/collections';

            $response = Http::withBody(
                $request->getContent(), 'application/json'
            )->get($urlString);


            $collectionArray = $response['hits']['hits'];
            $matchedIds = [];
            foreach (array_values($collectionArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $collectionsFiltered = Collection::whereRaw("1=1");
            foreach ($filters as $filter => $value) {
                foreach ($value as $key => $val) {
                    MMC::applySearchFilter($collectionsFiltered, $filter, $key, $val['terms']);
                }
            }

            $collectionModels = $collectionsFiltered->whereIn('id', $matchedIds)->get();

            $likeIds = [];
            foreach ($collectionModels as $d) {
                $likeIds[] = $d['id'];
            }

            //IDs that have been matched and IDs that have been filtered
            $slimSet = array_intersect($matchedIds, $likeIds);

            foreach ($collectionArray as $i => $collection) {
                if (!in_array($collection['_id'], $slimSet)) {
                    unset($collectionArray[$i]);
                    continue;
                }
                foreach ($collectionModels as $model){
                    if ((int) $collection['_id'] === $model['id']) {
                        $collectionArray[$i]['_source']['created_at'] = $model['created_at'];
                        break;
                    }
                }
            }

            $collectionArraySorted = $this->sortSearchResult($collectionArray, $sortField, $sortDirection);

            $perPage = request('perPage', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $collectionArraySorted, $perPage);
            return response()->json($paginatedData, 200);


        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/search/dur",
     *      summary="Keyword search across gateway data uses",
     *      description="Returns gateway data uses related to the provided query term(s)",
     *      tags={"Search-DataUses"},
     *      summary="Search@data_uses",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="diabetes data uses"),
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
     *              description="Field to sort by (score, created_at, projectTitle)",
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
     *              @OA\Property(property="current_page", type="integer", example="1"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="_source", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="projectTitle", type="string"),
     *                              @OA\Property(property="laySummary", type="string"),
     *                              @OA\Property(property="publicBenefitStatement", type="string"),
     *                              @OA\Property(property="technicalSummary", type="string"),
     *                              @OA\Property(property="fundersAndSponsors", type="string"),
     *                              @OA\Property(property="datasetTitles", type="array", @OA\Items()),
     *                              @OA\Property(property="keywords", type="array", @OA\Items())
     *                          )
     *                      ),
     *                      @OA\Property(property="highlight", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="laySummary", type="array", @OA\Items())
     *                          )
     *                      )
     *                  )
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function dataUses(Request $request): JsonResponse
    {
        try {
            $sort = $request->query('sort',"score:desc");   
        
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $filters = (isset($request['filters']) ? $request['filters'] : []);
            $urlString = env('SEARCH_SERVICE_URL') . '/search/dur';

            $response = Http::withBody(
                $request->getContent(), 'application/json'
            )->get($urlString);

            $durArray = $response['hits']['hits'];
            $matchedIds = [];
            foreach (array_values($durArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $durFiltered = Dur::whereRaw("1=1");
            foreach ($filters as $filter => $value) {
                foreach ($value as $key => $val) {
                    MMC::applySearchFilter($durFiltered, $filter, $key, $val['terms']);
                }
            }

            $durModels = $durFiltered->whereIn('id', $matchedIds)->get();

            $likeIds = [];
            foreach ($durModels as $d) {
                $likeIds[] = $d['id'];
            }

            //IDs that have been matched and IDs that have been filtered
            $slimSet = array_intersect($matchedIds, $likeIds);

            foreach ($durArray as $i => $dur) {
                if (!in_array($dur['_id'], $slimSet)) {
                    unset($durArray[$i]);
                    continue;
                }
                foreach ($durModels as $model){
                    if ((int) $dur['_id'] === $model['id']) {
                        $durArray[$i]['_source']['created_at'] = $model['created_at'];
                        break;
                    }
                }
            }

            $durArraySorted = $this->sortSearchResult($durArray, $sortField, $sortDirection);

            $perPage = request('perPage', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $durArraySorted, $perPage);
            return response()->json($paginatedData, 200);

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
