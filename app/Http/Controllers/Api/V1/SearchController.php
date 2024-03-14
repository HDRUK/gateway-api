<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\Dur;
use App\Models\Tool;

use App\Models\Dataset;
use App\Models\Collection;
use App\Models\Filter;
use App\Models\Publication;
use Illuminate\Http\Request;
use App\Exports\DataUseExport;

use App\Models\DatasetVersion;
use Illuminate\Http\JsonResponse;
use App\Exports\DatasetListExport;
use App\Exports\DatasetTableExport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

use Maatwebsite\Excel\Facades\Excel;
use App\Exceptions\NotFoundException;
use App\Http\Traits\PaginateFromArray;
use MetadataManagementController as MMC;
use Illuminate\Database\Eloquent\Casts\Json;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     *                      "BREATHE",
     *                      "HDRUK"
     *                   },
     *                  "populationSize": {
     *                      "includeUnreported": true,
     *                      "from": 100,
     *                      "to": 1000
     *                  }
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
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/datasets?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/datasets?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/datasets"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function datasets(Request $request): JsonResponse|BinaryFileResponse
    {
        try {
            $input = $request->all();

            $download = array_key_exists('download', $input) ? $input['download'] : false;
            $downloadType = array_key_exists('download_type', $input) ? $input['download_type'] : "list";
            $sort = $request->query('sort',"score:desc");   
        
            $tmp = explode(":", $sort);
            $sortInput = $tmp[0];
            $sortField = ($sortInput === 'title') ? 'shortTitle' : $sortInput;
            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $urlString = env('SEARCH_SERVICE_URL') . '/search/datasets';

            $filters = (isset($request['filters']) ? $request['filters'] : []);
            $aggs = Filter::where('type', 'dataset')->get()->toArray();
            $input['aggs'] = $aggs;

            $response = Http::post($urlString, $input);

            $datasetsArray = $response['hits']['hits'];
            $matchedIds = [];
            // join to created at from DB
            foreach (array_values($datasetsArray) as $i => $d) {
               $matchedIds[] = $d['_id'];
            }

            $datasetsModels = Dataset::with('versions')->whereIn('id', $matchedIds)->get()->toArray();
            foreach ($datasetsArray as $i => $dataset) {
                if (!in_array($dataset['_id'], $matchedIds)) {
                    unset($datasetsArray[$i]);
                    continue;
                }
                foreach ($datasetsModels as $model) {
                    if ((int) $dataset['_id'] === $model['id']) {
                        $datasetsArray[$i]['_source']['created_at'] = $model['versions'][0]['created_at'];
                        $datasetsArray[$i]['metadata'] = $model['versions'][0]['metadata'];
                        $datasetsArray[$i]['isCohortDiscovery'] = $model['is_cohort_discovery'];
                    }
                }
            }

            if ($download && $downloadType === "list") {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_service' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Search datasets export data - list",
                ]);
                return Excel::download(new DatasetListExport($datasetsArray), 'datasets.csv');
            }

            if ($download && $downloadType === "table") {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_service' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Search datasets export data - table",
                ]);
                return Excel::download(new DatasetTableExport($datasetsArray), 'datasets.csv');
            }

            $datasetsArraySorted = $this->sortSearchResult($datasetsArray, $sortField, $sortDirection);

            $perPage = request('perPage', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $datasetsArraySorted, $perPage);
            $aggs = collect([
                'aggregations' => $response['aggregations']
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Search datasets",
            ]);

            return response()->json($final, 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/search/similar/datasets",
     *      summary="Search for similar datasets",
     *      description="Returns top three gateway datasets most similar to the provided dataset",
     *      tags={"Search-Similar-Datasets"},
     *      summary="Search@similarDatasets",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit dataset id",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="id", type="integer", example=1)
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
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
     *                      @OA\Property(property="metadata", type="array", @OA\Items()
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function similarDatasets(Request $request): JsonResponse
    {
        try {
            $id = (string) $request['id'];
            $urlString = env('SEARCH_SERVICE_URL') . '/similar/datasets';

            $response = Http::post($urlString, ['id' => $id]);

            $datasetsArray = $response['hits']['hits'];
            $matchedIds = [];
            // join to data from DB
            foreach (array_values($datasetsArray) as $i => $d) {
               $matchedIds[] = $d['_id'];
            }

            $datasetsModels = Dataset::with('versions')->whereIn('id', $matchedIds)->get()->toArray();
            foreach ($datasetsArray as $i => $dataset) {
                foreach ($datasetsModels as $model) {
                    if ((int) $dataset['_id'] === $model['id']) {
                        $datasetsArray[$i]['_source']['created_at'] = $model['versions'][0]['created_at'];
                        $datasetsArray[$i]['metadata'] = $model['versions'][0]['metadata'];
                        $datasetsArray[$i]['isCohortDiscovery'] = $model['is_cohort_discovery'];
                    }
                }
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Search similar datasets",
            ]);

            return response()->json(['data' => $datasetsArray], 200);

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
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/tools?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/tools?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/tools"),
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

            $response = Http::post($urlString,$request->all());
           
            $toolsArray = $response['hits']['hits'];
            $matchedIds = [];
            foreach (array_values($toolsArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            //get all tools models that have been filtered and then matched by elastic
            $toolModels = Tool::whereIn('id', $matchedIds)->get();

            foreach ($toolsArray as $i => $tool) {
                if (!in_array($tool['_id'], $matchedIds)) {
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
            $aggs = collect([
                'aggregations' => $response['aggregations']
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Search tools",
            ]);

            return response()->json($final, 200);

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
            $input = $request->all();

            $sort = $request->query('sort',"score:desc");   
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $filters = (isset($request['filters']) ? $request['filters'] : []);
            $aggs = Filter::where('type', 'collection')->get()->toArray();
            $input['aggs'] = $aggs;

            $urlString = env('SEARCH_SERVICE_URL') . '/search/collections';
        
            $response = Http::post($urlString, $input);

            $collectionArray = $response['hits']['hits'];
            $matchedIds = [];
            foreach (array_values($collectionArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $collectionModels = Collection::whereIn('id', $matchedIds)->get();

            foreach ($collectionArray as $i => $collection) {
                if (!in_array($collection['_id'], $matchedIds)) {
                    unset($collectionArray[$i]);
                    continue;
                }
                foreach ($collectionModels as $model){
                    if ((int) $collection['_id'] === $model['id']) {
                        $collectionArray[$i]['_source']['created_at'] = $model['created_at'];
                        $collectionArray[$i]['name'] = $model['name'];
                        break;
                    }
                }
            }

            $collectionArraySorted = $this->sortSearchResult($collectionArray, $sortField, $sortDirection);

            $perPage = request('perPage', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $collectionArraySorted, $perPage);
            $aggs = collect([
                'aggregations' => $response['aggregations']
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Search collections",
            ]);

            return response()->json($final, 200);

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
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/dur?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/dur?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/dur"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function dataUses(Request $request): JsonResponse|BinaryFileResponse
    {
        try {
            $input = $request->all();
            $download = array_key_exists('download', $input) ? $input['download'] : false;
            $sort = $request->query('sort',"score:desc");   
        
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $filters = (isset($request['filters']) ? $request['filters'] : []);
            $aggs = Filter::where('type', 'dataUseRegister')->get()->toArray();
            $input['aggs'] = $aggs;

            $urlString = env('SEARCH_SERVICE_URL') . '/search/dur';

            $response = Http::post($urlString, $input);

            $durArray = $response['hits']['hits'];
            $matchedIds = [];
            foreach (array_values($durArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $durModels = Dur::whereIn('id', $matchedIds)->with('datasets')->get();

            foreach ($durArray as $i => $dur) {
                if (!in_array($dur['_id'], $matchedIds)) {
                    unset($durArray[$i]);
                    continue;
                }
                foreach ($durModels as $model){
                    if ((int) $dur['_id'] === $model['id']) {
                        $durArray[$i]['_source']['created_at'] = $model['created_at'];
                        $durArray[$i]['projectTitle'] = $model['project_title'];
                        $durArray[$i]['organisationName'] = $model['organisation_name'];
                        $durArray[$i]['team'] = $model['team'];
                        $durArray[$i]['mongoObjectId'] = $model['mongo_object_id']; // remove
                        $durArray[$i]['datasetTitles'] = $this->durDatasetTitles($model);
                        break;
                    }
                }
            }

            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_service' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Search dur export data",
                ]);
                return Excel::download(new DataUseExport($durArray), 'dur.csv');
            }

            $durArraySorted = $this->sortSearchResult($durArray, $sortField, $sortDirection);

            $perPage = request('perPage', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $durArraySorted, $perPage);
            $aggs = collect([
                'aggregations' => $response['aggregations']
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Search dur",
            ]);

            return response()->json($final, 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Examples(
     *      example="filterPublicationsExample",
     *      summary="Example Filters payload for searching publications",
     *      value={
     *          "filters": {
     *               "paper": {
     *                   "publicationType": {
     *                        "Journal Article",
     *                        "Preprint"
     *                    },
     *                    "publicationDate": {
     *                        "2020", "2024"
     *                    }
     *               }
     *           }
     *       }
     * ),
     * @OA\Post(
     *      path="/api/v1/search/publications",
     *      summary="Keyword search across gateway hosted publications",
     *      description="Returns gateway publications related to the provided query term(s)",
     *      tags={"Search-Publications"},
     *      summary="Search@publications",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="diabetes publications"),
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
     *              @OA\Property(property="current_page", type="integer", example="1"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="_source", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="abstract", type="string"),
     *                              @OA\Property(property="authors", type="string"),
     *                              @OA\Property(property="datasetTitles", type="string"),
     *                              @OA\Property(property="journalName", type="string"),
     *                              @OA\Property(property="publicationDate", type="string"),
     *                              @OA\Property(property="publicationType", type="array", @OA\Items()),
     *                              @OA\Property(property="title", type="string")
     *                          )
     *                      ),
     *                      @OA\Property(property="highlight", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="title", type="array", @OA\Items()),
     *                              @OA\Property(property="abstract", type="array", @OA\Items())
     *                          )
     *                      )
     *                  )
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/dur?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/dur?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/dur"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function publications(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $sort = $request->query('sort',"score:desc");   
        
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $filters = (isset($request['filters']) ? $request['filters'] : []);
            $aggs = Filter::where('type', 'paper')->get()->toArray();
            $input['aggs'] = $aggs;

            $urlString = env('SEARCH_SERVICE_URL') . '/search/publications';

            $response = Http::post($urlString, $input);

            $pubArray = $response['hits']['hits'];
            $matchedIds = [];
            foreach (array_values($pubArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $pubModels = Publication::whereIn('id', $matchedIds)->get();

            foreach ($pubArray as $i => $p) {
                if (!in_array($p['_id'], $matchedIds)) {
                    unset($pubArray[$i]);
                    continue;
                }
                foreach ($pubModels as $model){
                    if ((int) $p['_id'] === $model['id']) {
                        $pubArray[$i]['_source']['created_at'] = $model['created_at'];
                        $pubArray[$i]['paper_title'] = $model['paper_title'];
                        $pubArray[$i]['abstract'] = $model['abstract'];
                        $pubArray[$i]['authors'] = $model['authors'];
                        $pubArray[$i]['journal_name'] = $model['journal_name'];
                        $pubArray[$i]['year_of_publication'] = $model['year_of_publication'];
                        break;
                    }
                }
            }

            $pubArraySorted = $this->sortSearchResult($pubArray, $sortField, $sortDirection);

            $perPage = request('perPage', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $pubArraySorted, $perPage);
            $aggs = collect([
                'aggregations' => $response['aggregations']
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Search publications",
            ]);

            return response()->json($final, 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Find dataset titles associated with a given Dur model instance.
     * Returns an array of titles.
     * 
     * @param Dur $durMatch The Dur model the find dataset titles for
     * 
     * @return array
     */
    private function durDatasetTitles(Dur $durMatch): array
    {
        $datasetTitles = array();
        foreach ($durMatch['datasets'] as $d) {
            $metadata = Dataset::where(['id' => $d['id']])
                ->first()
                ->latestVersion()
                ->metadata;
            $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
        }
        usort($datasetTitles, 'strcasecmp');
        return $datasetTitles;
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
