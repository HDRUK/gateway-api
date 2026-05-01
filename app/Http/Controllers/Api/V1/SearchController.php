<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\DataProviderCollExport;
use App\Exports\DataProviderExport;
use App\Exports\DatasetListExport;
use App\Exports\DatasetTableExport;
use App\Exports\DataUseExport;
use App\Exports\PublicationExport;
use App\Exports\ToolListExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\DOISearch;
use App\Http\Requests\Search\PublicationSearch;
use App\Http\Requests\Search\Search;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Traits\IndexElastic;
use App\Http\Traits\LoggingContext;
use App\Http\Traits\PaginateFromArray;
use App\Models\Collection;
use App\Models\CollectionHasPublication;
use App\Models\DataAccessTemplate;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasTool;
use App\Models\Dur;
use App\Models\DurHasPublication;
use App\Models\DurHasTool;
use App\Models\Filter;
use App\Models\License;
use App\Models\ProgrammingLanguage;
use App\Models\ProgrammingPackage;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use App\Models\PublicationHasTool;
use App\Models\Team;
use App\Models\Tool;
use App\Models\ToolHasProgrammingLanguage;
use App\Models\ToolHasProgrammingPackage;
use App\Models\ToolHasTypeCategory;
use App\Models\TypeCategory;
use App\Models\User;
use Auditor;
use CloudLogger;
use Config;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Services\Search\FilterCache;
use App\Services\Search\DatasetHydrator;
use App\Services\Search\ToolHydrator;
use App\Services\Search\CollectionHydrator;
use App\Services\Search\DataUseHydrator;
use App\Services\Search\PublicationHydrator;
use App\Services\Search\DataCustodianNetworkHydrator;
use App\Services\Search\DataCustodianHydrator;

class SearchController extends Controller
{
    use PaginateFromArray;
    use LoggingContext;

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
     *                  @OA\Property(property="filters", type="string", example={"filtersExample": @OA\Schema(ref="#/components/examples/filtersExample")}),
     *                  @OA\Property(property="per_page", type="integer", example=25)
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
    public function datasets(Search $request): JsonResponse|BinaryFileResponse
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();

        $download = array_key_exists('download', $input) ? $input['download'] : false;
        $downloadType = array_key_exists('download_type', $input) ? $input['download_type'] : 'list';
        $sort = $request->query('sort', 'score:desc');
        $viewType = $request->query('view_type', 'full');

        $tmp = explode(':', $sort);
        $sortInput = $tmp[0];
        $sortField = ($sortInput === 'title') ? 'shortTitle' : $sortInput;
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        try {
            $input['aggs'] = FilterCache::get('dataset', enabledOnly: true);

            $urlString = config('gateway.search_service_url') . '/search/datasets';
            $response = Http::withHeaders($loggingContext)->post($urlString, $input);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'No response from ' . $urlString,
                ], 404);
            }
            $response = $response->json();

            if (
                !isset($response['hits']) || !is_array($response['hits']) ||
                !isset($response['hits']['hits']) || !is_array($response['hits']['hits']) ||
                !isset($response['hits']['total']['value'])
            ) {
                return response()->json([
                    'message' => 'Hits not being properly returned by the search service'
                ], 404);
            }

            $datasetsArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = array_column($datasetsArray, '_id');

            $datasetsArray = (new DatasetHydrator())->hydrate($datasetsArray, $viewType);

            if ($download && strtolower($downloadType) === 'list') {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search datasets export data - list',
                ]);
                \Log::info('Search datasets export data - list', $loggingContext);
                return Excel::download(new DatasetListExport($datasetsArray), 'datasets.csv');
            }

            if ($download && strtolower($downloadType) === 'table') {
                Auditor::log([
                    'action_type' => 'POST',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search datasets export data - table',
                ]);
                \Log::info('Search datasets export data - table', $loggingContext);
                return Excel::download(new DatasetTableExport($datasetsArray), 'datasets.csv');
            }

            $datasetsArray = $this->sortSearchResult($datasetsArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $datasetsArray, $perPage);
            unset($datasetsArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids'          => $matchedIds,
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search datasets',
            ]);

            return response()->json($final, 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

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
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $id = (string)$request['id'];

        try {
            $urlString = config('gateway.search_service_url') . '/similar/datasets';
            $response = Http::withHeaders($loggingContext)->post($urlString, ['id' => $id]);

            $datasetsArray = $response['hits']['hits'];
            $matchedIds = array_column($datasetsArray, '_id');

            $datasetsArray = (new DatasetHydrator())->hydrate($datasetsArray);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search similar datasets',
            ]);

            return response()->json(['data' => $datasetsArray], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            \Log::info($e->getMessage(), $loggingContext);

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
     *
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
     *                      ),
     *                      @OA\Property(property="uploader", type="string"),
     *                      @OA\Property(property="team_name", type="string"),
     *                      @OA\Property(property="type_category", type="array", @OA\Items()),
     *                      @OA\Property(property="license", type="string"),
     *                      @OA\Property(property="programming_language", type="array", @OA\Items()),
     *                      @OA\Property(property="programming_package", type="array", @OA\Items()),
     *                      @OA\Property(property="datasets", type="array", @OA\Items()),
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
    public function tools(Search $request): JsonResponse|BinaryFileResponse
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();

        $download = array_key_exists('download', $input) ? $input['download'] : false;
        $sort = $request->query('sort', 'score:desc');

        $tmp = explode(':', $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        try {
            $input['aggs'] = FilterCache::get('tool');

            try {
                $urlString = config('gateway.search_service_url') . '/search/tools';
                $response = Http::withHeaders($loggingContext)->post($urlString, $input);
            } catch (ConnectionException $e) {
                Auditor::log([
                    'action_type' => 'EXCEPTION',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => $e->getMessage(),
                ]);
                \Log::info($e->getMessage(), $loggingContext);
                throw new Exception('Operation timeout: The search query is too long. Please try searching with fewer keywords');
            } catch (Exception $e) {
                Auditor::log([
                    'action_type' => 'EXCEPTION',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => $e->getMessage(),
                ]);
                \Log::info($e->getMessage(), $loggingContext);
                throw new Exception($e->getMessage());
            }

            $toolsArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = array_column($toolsArray, '_id');

            $toolsArray = (new ToolHydrator())->hydrate($toolsArray);

            $toolsArray = $this->sortSearchResult($toolsArray, $sortField, $sortDirection);

            if ($download) {
                Auditor::log([
                    'action_type' => 'POST',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search tools export data - list',
                ]);
                \Log::info('Search tools export data - list', $loggingContext);
                return Excel::download(new ToolListExport($toolsArray), 'tools.csv');
            }

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $toolsArray, $perPage);
            unset($toolsArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids'          => $matchedIds,
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search tools',
            ]);

            return response()->json($final, 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

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
    public function collections(Search $request): JsonResponse
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();

        $sort = $request->query('sort', 'score:desc');
        $tmp = explode(':', $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        try {
            $input['aggs'] = FilterCache::get('collection');

            $urlString = config('gateway.search_service_url') . '/search/collections';
            $response = Http::withHeaders($loggingContext)->post($urlString, $input);

            $collectionArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = array_column($collectionArray, '_id');

            $collectionArray = (new CollectionHydrator())->hydrate($collectionArray);
            $collectionArray = $this->sortSearchResult($collectionArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $collectionArray, $perPage);
            unset($collectionArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids'          => $matchedIds,
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search collections',
            ]);

            return response()->json($final, 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

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
     *      @OA\Parameter(
     *          name="download",
     *          in="query",
     *          description="Download a csv of the results (default: false)",
     *          example="true",
     *          @OA\Schema(
     *              type="boolean",
     *              example="false",
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
    public function dataUses(Search $request): JsonResponse|BinaryFileResponse
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();

        $download = array_key_exists('download', $input) ? $input['download'] : false;
        $sort = $request->query('sort', 'score:desc');

        $tmp = explode(':', $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        $input['aggs'] = FilterCache::get('dataUseRegister');

        try {
            $urlString = config('gateway.search_service_url') . '/search/dur';
            $response = Http::withHeaders($loggingContext)->post($urlString, $input);
        } catch (ConnectionException $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);
            throw new Exception('Operation timeout: The search query is too long. Please try searching with fewer keywords');
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);
            throw new Exception($e->getMessage());
        }

        try {
            $durArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = array_column($durArray, '_id');

            $durArray = (new DataUseHydrator())->hydrate($durArray);

            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search dur export data',
                ]);
                \Log::info('Search dur export data', $loggingContext);
                return Excel::download(new DataUseExport($durArray), 'dur.csv');
            }

            $durArray = $this->sortSearchResult($durArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $durArray, $perPage);
            unset($durArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids'          => $matchedIds,
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search dur',
            ]);

            return response()->json($final, 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

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
     *      @OA\Parameter(
     *          name="source",
     *          in="query",
     *          description="Which source to search ('GAT' or 'FED', default: 'GAT')",
     *          example="GAT",
     *          @OA\Schema(
     *              type="string",
     *              enum={"GAT", "FED"},
     *              description="Which source to search (GAT - Gateway or FED - federated e.g. EuropePMC)",
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
    public function publications(PublicationSearch $request): JsonResponse|BinaryFileResponse
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $download = array_key_exists('download', $input) ? $input['download'] : false;
        $sort = $request->query('sort', 'score:desc');

        $tmp = explode(':', $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        $source = !is_null($input['source']) ? $input['source'] : 'GAT';

        try {
            $matchedIds = null;
            if ($source === 'GAT') {
                $input['aggs'] = FilterCache::get('paper');

                try {
                    $urlString = config('gateway.search_service_url') . '/search/publications';
                    $response = Http::withHeaders($loggingContext)->post($urlString, $input);
                } catch (ConnectionException $e) {
                    Auditor::log([
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $e->getMessage(),
                    ]);
                    \Log::info($e->getMessage(), $loggingContext);
                    throw new Exception('Operation timeout: The search query is too long. Please try searching with fewer keywords');
                } catch (Exception $e) {
                    Auditor::log([
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $e->getMessage(),
                    ]);
                    \Log::info($e->getMessage(), $loggingContext);
                    throw new Exception($e->getMessage());
                }

                $pubArray = $response['hits']['hits'];
                $totalResults = $response['hits']['total']['value'];
                $matchedIds = array_column($pubArray, '_id');

                $pubArray = (new PublicationHydrator())->hydrate($pubArray);
            } else {
                if (isset($input['query']) && is_array($input['query'])) {
                    $urlString = config('gateway.search_service_url') . '/search/federated_papers/field_search/array';
                } else {
                    if (isset($input['query']) && $this->isDoi($input['query'])) {
                        $urlString = config('gateway.search_service_url') . '/search/federated_papers/doi';
                    } else {
                        $urlString = config('gateway.search_service_url') . '/search/federated_papers/field_search';
                    }
                }
                $input['field'] = ['TITLE', 'ABSTRACT', 'METHODS'];

                try {
                    $response = Http::withHeaders($loggingContext)->post($urlString, $input);

                } catch (ConnectionException $e) {
                    Auditor::log([
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $e->getMessage(),
                    ]);
                    \Log::info($e->getMessage(), $loggingContext);
                    throw new Exception('Operation timeout: The search query is too long. Please try searching with fewer keywords');
                } catch (Exception $e) {
                    Auditor::log([
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $e->getMessage(),
                    ]);
                    \Log::info($e->getMessage(), $loggingContext);
                    throw new Exception($e->getMessage());
                }

                $pubArray = $response['resultList']['result'] ?? [];
                $totalResults = $response['hitCount'];
                $pubArray = array_filter($pubArray, function ($paper) {
                    return Arr::has($paper, 'fullTextUrlList.fullTextUrl.0.url');
                });

                foreach ($pubArray as $i => $paper) {
                    $pubArray[$i]['id'] = $paper['id'];
                    $pubArray[$i]['_source']['year_of_publication'] = $paper['pubYear'];
                    $pubArray[$i]['_source']['title'] = $paper['title'];
                    $pubArray[$i]['paper_title'] = $paper['title'];
                    $pubArray[$i]['abstract'] = preg_replace('/<h4>(.*?)<\/h4>/', '', $paper['abstractText']);
                    $pubArray[$i]['authors'] = $paper['authorString'];
                    $pubArray[$i]['journal_name'] = isset($paper['journalInfo']) ?
                        $paper['journalInfo']['journal']['title'] : '';
                    $pubArray[$i]['year_of_publication'] = $paper['pubYear'];
                    $pubArray[$i]['full_text_url'] = Arr::get($paper, 'fullTextUrlList.fullTextUrl.0.url');
                    $pubArray[$i]['url'] = Arr::get($paper, 'fullTextUrlList.fullTextUrl.0.url');
                }
            }

            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search publications export data',
                ]);
                \Log::info('Search publications export data', $loggingContext);
                return Excel::download(new PublicationExport($pubArray), 'publications.csv');
            }

            $pubArray = $this->sortSearchResult($pubArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $pubArray, $perPage);
            unset($pubArray);

            $arrAggs = [
                'aggregations' => isset($response['aggregations']) ? $response['aggregations'] : [],
                'elastic_total' => $totalResults,
            ];
            if ($matchedIds) {
                $arrAggs['ids'] = $matchedIds;
            }
            $aggs = collect($arrAggs);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search publications',
            ]);

            return response()->json($final, 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/search/doi",
     *      summary="DOI search of EuropePMC publications",
     *      description="Returns publications from EuropePMC matching a give DOI",
     *      tags={"Search-Publications"},
     *      summary="Search@publications",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="10.12345/fht"),
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="No match found",
     *      )
     * )
     */
    public function doiSearch(DOISearch $request): Response | JsonResponse
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();

        try {
            $urlString = config('gateway.search_service_url') . '/search/federated_papers/doi';
            $response = Http::withHeaders($loggingContext)->post($urlString, $input);

            if (!isset($response['resultList']['result']) || !is_array($response['resultList']['result'])) {
                CloudLogger::write([
                    'action_type' => 'SEARCH',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Malformed response from search service. Response was: ' . json_encode($response->json()),
                ]);
                return response()->json([
                    'message' => 'Malformed response from search service. Response was: ' . json_encode($response->json())
                ], 404);
            } else {
                $pubMatch = $response['resultList']['result'];
            }

            $pubResult = array();
            if (count($pubMatch) === 1) {
                $result = $pubMatch[0];
                $pubResult['title'] = $result['title'];
                $pubResult['authors'] = $result['authorString'];
                $pubResult['abstract'] = $result['abstractText'];
                $pubResult['is_preprint'] = str_contains($result['id'], 'PPR') ? true : false;
                if (!$pubResult['is_preprint']) {
                    $pubResult['journal_name'] = $result['journalInfo']['journal']['title'];
                    $pubResult['publication_year'] = $result['pubYear'];
                } else {
                    $pubResult['journal_name'] = null;
                    $pubResult['publication_year'] = null;
                }
                $pubResult['fullTextUrl'] = isset($result['fullTextUrlList']['fullTextUrl']) ? $result['fullTextUrlList']['fullTextUrl'] : null;
                $pubResult['firstPublicationDate'] = isset($result['firstPublicationDate']) ? $result['firstPublicationDate'] : null;
            } else {
                return response()->noContent();
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search for publication by doi',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $pubResult
            ], 200);
        } catch (Exception $e) {
            \Log::info($e->getMessage(), $loggingContext);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/search/data_custodian_networks",
     *      summary="Keyword search across gateway data custodian networks",
     *      description="Returns gateway data custodian networks related to the provided query term(s)",
     *      tags={"Search-DataCustodianNetworks"},
     *      summary="Search@data_custodian_networks",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="national data custodian networks"),
     *                  @OA\Property(property="filters", type="string", example={"filtersExample": @OA\Schema(ref="#/components/examples/filtersExample")})
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
     *              description="Field to sort by (score, updated_at, name)",
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
     *                              @OA\Property(property="id", type="string"),
     *                              @OA\Property(property="name", type="string"),
     *                              @OA\Property(property="img_url", type="string"),
     *                              @OA\Property(property="datasetTitles", type="array", @OA\Items()),
     *                              @OA\Property(property="geographicLocation", type="array", @OA\Items())
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
    public function dataCustodianNetworks(Request $request): JsonResponse|BinaryFileResponse
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $download = array_key_exists('download', $input) ? $input['download'] : false;
        $sort = $request->query('sort', 'score:desc');

        $tmp = explode(':', $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        try {
            $input['aggs'] = FilterCache::get('dataProviderColl');

            $urlString = config('gateway.search_service_url') . '/search/data_custodian_networks';
            $response = Http::withHeaders($loggingContext)->post($urlString, $input);

            $dataCustodianNetworksArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = array_column($dataCustodianNetworksArray, '_id');

            $dataCustodianNetworksArray = (new DataCustodianNetworkHydrator())->hydrate($dataCustodianNetworksArray);

            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search data custodian network export data',
                ]);
                \Log::info('Search data custodian network export data', $loggingContext);
                return Excel::download(new DataProviderCollExport($dataCustodianNetworksArray), 'dataCustodianNetworks.csv');
            }

            $dataCustodianNetworksArray = $this->sortSearchResult($dataCustodianNetworksArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $dataCustodianNetworksArray, $perPage);
            unset($dataCustodianNetworksArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search data custodian networks',
            ]);

            return response()->json($final, 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/search/data_custodians",
     *      summary="Keyword search across gateway data custodians",
     *      description="Returns gateway data custodians related to the provided query term(s)",
     *      tags={"Search-DataCustodians"},
     *      summary="Search@data_custodians",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="national data custodians"),
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
     *              description="Field to sort by (score, updated_at, name)",
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
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Number of results to return per page",
     *          example="25",
     *          @OA\Schema(
     *              type="integer",
     *              description="Number of results to return per page",
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
     *                              @OA\Property(property="team_logo", type="string"),
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
    public function dataCustodians(Search $request): JsonResponse|BinaryFileResponse
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $download = array_key_exists('download', $input) ? $input['download'] : false;
        $sort = $request->query('sort', 'score:desc');

        $tmp = explode(':', $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        try {
            $input['aggs'] = FilterCache::get('dataProvider');

            $urlString = config('gateway.search_service_url') . '/search/data_providers';
            $response = Http::withHeaders($loggingContext)->post($urlString, $input);

            $dataCustodianArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = array_column($dataCustodianArray, '_id');

            $dataCustodianArray = (new DataCustodianHydrator())->hydrate($dataCustodianArray);

            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search data provider export data',
                ]);
                \Log::info('Search data provider export data', $loggingContext);
                return Excel::download(new DataProviderExport($dataCustodianArray), 'dataCustodian.csv');
            }

            $dataCustodianArray = $this->sortSearchResult($dataCustodianArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $dataCustodianArray, $perPage);
            unset($dataCustodianArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids'          => $matchedIds,
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Search data provider',
            ]);

            return response()->json($final, 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    private function sortSearchResult(array $resultArray, string $sortField, string $sortDirection): array
    {
        if ($sortField === 'score') {
            return $sortDirection === 'desc' ? $resultArray : array_reverse($resultArray);
        }

        if ($sortDirection === 'asc') {
            usort($resultArray, fn ($a, $b) => $a['_source'][$sortField] <=> $b['_source'][$sortField]);
        } else {
            usort($resultArray, function ($a, $b) use ($sortField) {
                $aVal = $a['_source'][$sortField];
                $bVal = $b['_source'][$sortField];

                if (strtotime($aVal) !== false) {
                    return strtotime($bVal) <=> strtotime($aVal);
                } elseif (is_string($aVal)) {
                    return strtoupper($bVal) <=> strtoupper($aVal);
                } else {
                    return $bVal <=> $aVal;
                }
            });
        }

        return $resultArray;
    }

    private function isDoi(string $query): bool
    {
        $pattern = '/10.\d{4,9}[-._;()\/:a-zA-Z0-9]+(?=[\s,\/]|$)/i';
        return (bool) preg_match($pattern, $query);
    }
}
