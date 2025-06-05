<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use CloudLogger;
use App\Models\Dur;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Filter;
use App\Models\Dataset;
use App\Models\License;
use App\Models\Collection;
use App\Models\DurHasTool;
use App\Models\Publication;
use App\Models\TypeCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exports\DataUseExport;
use App\Models\DatasetVersion;
use App\Exports\ToolListExport;
use App\Models\DataProviderColl;
use App\Http\Traits\IndexElastic;
use App\Models\DurHasPublication;
use Illuminate\Http\JsonResponse;
use App\Exports\DatasetListExport;
use App\Exports\PublicationExport;
use App\Models\ProgrammingPackage;
use App\Models\PublicationHasTool;
use App\Exports\DataProviderExport;
use App\Exports\DatasetTableExport;
use App\Models\ProgrammingLanguage;
use App\Models\ToolHasTypeCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\DatasetVersionHasTool;
use App\Http\Traits\PaginateFromArray;
use App\Exports\DataProviderCollExport;
use App\Http\Requests\Search\DOISearch;
use App\Models\DataProviderCollHasTeam;
use App\Models\CollectionHasPublication;
use App\Models\ToolHasProgrammingPackage;
use App\Models\ToolHasProgrammingLanguage;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Database\Eloquent\Casts\Json;
use App\Http\Requests\Search\PublicationSearch;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SearchController extends Controller
{
    use IndexElastic;
    use GetValueByPossibleKeys;
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
    public function datasets(Request $request): JsonResponse|BinaryFileResponse
    {
        try {
            $input = $request->all();

            $download = array_key_exists('download', $input) ? $input['download'] : false;
            $downloadType = array_key_exists('download_type', $input) ? $input['download_type'] : 'list';
            $sort = $request->query('sort', 'score:desc');
            $viewType = $request->query('view_type', 'full');

            $tmp = explode(':', $sort);
            $sortInput = $tmp[0];
            $sortField = ($sortInput === 'title') ? 'shortTitle' : $sortInput;
            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $aggs = Filter::where('type', 'dataset')->where('enabled', 1)->get()->toArray();
            $input['aggs'] = $aggs;

            $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/datasets';
            $response = Http::post($urlString, $input);

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
            $matchedIds = [];
            // join to created at from DB
            foreach (array_values($datasetsArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            foreach ($matchedIds as $i => $matchedId) {
                $model = Dataset::with('team')->where('id', $matchedId)
                    ->first();

                if (!$model) {
                    \Log::warning('Elastic found id=' . $matchedId . ' which is not an existing dataset');
                    if (isset($datasetsArray[$i])) {
                        unset($datasetsArray[$i]);
                    }
                    continue;
                }

                $model['metadata'] = $model->latestVersion()['metadata']['metadata'];

                $metadata = $model['metadata'];

                if (isset($metadata['summary']['publisher']['gatewayId']) && strpos($metadata['summary']['publisher']['gatewayId'], '-') !== false) {
                    // then we're in pid land
                    $id = $metadata['summary']['publisher']['gatewayId'];
                    $team = Team::where('pid', $id)->first();
                    if ($team) {
                        $metadata['summary']['publisher']['gatewayId'] = $team->id;
                        $model['metadata'] = $metadata;
                    }
                } else {
                    // were in id land but we be a string
                    $gatewayId = $metadata['summary']['publisher']['gatewayId'];
                    $metadata['summary']['publisher']['gatewayId'] = (int)$gatewayId;
                }
                $model = $model->toArray();

                $datasetsArray[$i]['_source']['created_at'] = $model['created_at'];
                $datasetsArray[$i]['_source']['updated_at'] = $model['updated_at'];

                if (strtolower($viewType) === 'mini') {
                    $datasetsArray[$i]['metadata'] = $this->trimPayload($model);
                } else {
                    $datasetsArray[$i]['metadata'] = $model['metadata'];
                }

                $datasetsArray[$i]['isCohortDiscovery'] = $model['is_cohort_discovery'];
                $datasetsArray[$i]['dataProviderColl'] = $this->getDataProviderColl($model);
                $datasetsArray[$i]['team']['id'] = $model['team']['id'];
                $datasetsArray[$i]['team']['is_question_bank'] = $model['team']['is_question_bank'];
                $datasetsArray[$i]['team']['name'] = $model['team']['name'];
                $datasetsArray[$i]['team']['member_of'] = $model['team']['member_of'];
                $datasetsArray[$i]['team']['is_dar'] = $model['team']['is_dar'];
                $datasetsArray[$i]['team']['dar_modal_header'] = $model['team']['dar_modal_header'];
                $datasetsArray[$i]['team']['dar_modal_content'] = $model['team']['dar_modal_content'];
                $datasetsArray[$i]['team']['dar_modal_footer'] = $model['team']['dar_modal_footer'];
            }


            if ($download && strtolower($downloadType) === 'list') {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search datasets export data - list',
                ]);
                return Excel::download(new DatasetListExport($datasetsArray), 'datasets.csv');
            }

            if ($download && strtolower($downloadType) === 'table') {
                Auditor::log([
                    'action_type' => 'POST',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search datasets export data - table',
                ]);
                return Excel::download(new DatasetTableExport($datasetsArray), 'datasets.csv');
            }

            $datasetsArray = $this->sortSearchResult($datasetsArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $datasetsArray, $perPage);
            unset($datasetsArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids' => $matchedIds,
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
            $id = (string)$request['id'];
            $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/similar/datasets';
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
                    if ((int)$dataset['_id'] === $model['id']) {
                        $datasetsArray[$i]['_source']['created_at'] = $model['versions'][0]['created_at'];
                        $datasetsArray[$i]['metadata'] = $model['versions'][0]['metadata'];
                        $datasetsArray[$i]['isCohortDiscovery'] = $model['is_cohort_discovery'];
                    }
                }
            }

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
    public function tools(Request $request): JsonResponse|BinaryFileResponse
    {
        try {
            $input = $request->all();

            $download = array_key_exists('download', $input) ? $input['download'] : false;

            $sort = $request->query('sort', 'score:desc');

            $tmp = explode(':', $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $aggs = Filter::where('type', 'tool')->get()->toArray();
            $input['aggs'] = $aggs;

            try {
                $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/tools';
                $response = Http::post($urlString, $input);
            } catch (ConnectionException $e) {
                Auditor::log([
                    'action_type' => 'EXCEPTION',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => $e->getMessage(),
                ]);
                throw new Exception('Operation timeout: The search query is too long. Please try searching with fewer keywords');
            } catch (Exception $e) {
                Auditor::log([
                    'action_type' => 'EXCEPTION',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => $e->getMessage(),
                ]);
                throw new Exception($e->getMessage());
            }

            $toolsArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = [];
            foreach (array_values($toolsArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            //get all tools models that have been filtered and then matched by elastic
            $toolModels = Tool::whereIn('id', $matchedIds)->get();

            foreach ($toolsArray as $i => $tool) {
                $foundFlag = false;
                foreach ($toolModels as $model) {
                    if ((int)$tool['_id'] === $model['id']) {

                        $toolsArray[$i]['name'] = $model['name'];
                        $toolsArray[$i]['description'] = $model['description'];

                        // uploader
                        $user = User::where('id', $model['user_id'])->first();
                        $toolsArray[$i]['uploader'] = $user ? $user->name : '';

                        // team
                        $team = Team::where('id', $model['team_id'])->first();
                        $toolsArray[$i]['team_name'] = $team ? $team->name : '';

                        // type_category
                        $toolHasTypeCategoryIds = ToolHasTypeCategory::where('tool_id', $model['id'])
                            ->pluck('type_category_id')->all();
                        $toolsArray[$i]['type_category'] = $toolHasTypeCategoryIds ?
                            TypeCategory::whereIn('id', $toolHasTypeCategoryIds)->pluck('name')->all() : [];

                        // license
                        $license = License::where('id', $model['license'])->first();
                        $toolsArray[$i]['license'] = $license ? $license->label : '';

                        // programming_language
                        $toolHasProgrammingLangIds = ToolHasProgrammingLanguage::where('tool_id', $model['id'])
                            ->pluck('programming_language_id')->all();
                        $toolsArray[$i]['programming_language'] = $toolHasProgrammingLangIds ?
                            ProgrammingLanguage::whereIn('id', $toolHasProgrammingLangIds)
                            ->pluck('name')->all() : [];

                        // programming_package
                        $toolHasProgrammingPackageIds = ToolHasProgrammingPackage::where('tool_id', $model['id'])
                            ->pluck('programming_package_id')->all();
                        $toolsArray[$i]['programming_package'] = $toolHasProgrammingPackageIds ?
                            ProgrammingPackage::whereIn('id', $toolHasProgrammingPackageIds)
                            ->pluck('name')->all() : [];

                        // datasets
                        $datasetVersionHasToolIds = DatasetVersionHasTool::where('tool_id', $model['id'])
                            ->pluck('dataset_version_id')->all();
                        $datasetHasToolIds = DatasetVersion::whereIn('id', $datasetVersionHasToolIds)
                            ->pluck('dataset_id')->all();

                        $toolsArray[$i]['datasets'] = $this->getDatasetTitle($datasetHasToolIds);

                        $toolsArray[$i]['dataProviderColl'] = $this->getDataProviderColl($model->toArray());

                        $toolsArray[$i]['durTitles'] = $this->toolDurTitles($model['id']);

                        $toolsArray[$i]['_source']['programmingLanguage'] = $model['tech_stack'];
                        $category = null;
                        if ($model->category) {
                            $category = $model->category['name'];
                        }
                        $toolsArray[$i]['_source']['category'] = $category;
                        $toolsArray[$i]['_source']['created_at'] = $model['created_at'];
                        $toolsArray[$i]['_source']['updated_at'] = $model['updated_at'];
                        $foundFlag = true;
                        break;
                    }
                }
                if (!$foundFlag) {
                    unset($toolsArray[$i]);
                    continue;
                }
            }

            $toolsArray = $this->sortSearchResult($toolsArray, $sortField, $sortDirection);

            if ($download) {
                Auditor::log([
                    'action_type' => 'POST',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search tools export data - list',
                ]);
                return Excel::download(new ToolListExport($toolsArray), 'tools.csv');
            }

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $toolsArray, $perPage);
            unset($toolsArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids' => $matchedIds,
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

            $sort = $request->query('sort', 'score:desc');
            $tmp = explode(":", $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $aggs = Filter::where('type', 'collection')->get()->toArray();
            $input['aggs'] = $aggs;

            $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/collections';
            $response = Http::post($urlString, $input);

            $collectionArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = [];
            foreach (array_values($collectionArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $collectionModels = Collection::whereIn('id', $matchedIds)->get();

            foreach ($collectionArray as $i => $collection) {
                $foundFlag = false;
                foreach ($collectionModels as $model) {
                    if ((int)$collection['_id'] === $model['id']) {
                        $collectionArray[$i]['_source']['updated_at'] = $model['updated_at'];
                        $collectionArray[$i]['name'] = $model['name'];
                        $collectionArray[$i]['dataProviderColl'] = $this->getDataProviderColl($model->toArray());
                        $collectionArray[$i]['image_link'] = (is_null($model['image_link']) || strlen(trim($model['image_link'])) === 0
                                                             ? null
                                                             : (preg_match('/^https?:\/\//', $model['image_link'])
                                                             ? $model['image_link']
                                                             : Config::get('services.media.base_url') . $model['image_link']));
                        $foundFlag = true;
                        break;
                    }
                }
                if (!$foundFlag) {
                    unset($collectionArray[$i]);
                    continue;
                }
            }

            $collectionArray = $this->sortSearchResult($collectionArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $collectionArray, $perPage);
            unset($collectionArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids' => $matchedIds,
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
    public function dataUses(Request $request): JsonResponse|BinaryFileResponse
    {
        $input = $request->all();
        $download = array_key_exists('download', $input) ? $input['download'] : false;
        $sort = $request->query('sort', 'score:desc');

        $tmp = explode(":", $sort);
        $sortField = $tmp[0];

        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        $aggs = Filter::where('type', 'dataUseRegister')->get()->toArray();
        $input['aggs'] = $aggs;

        try {
            $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/dur';
            $response = Http::post($urlString, $input);
        } catch (ConnectionException $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception('Operation timeout: The search query is too long. Please try searching with fewer keywords');
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }

        try {
            $durArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];

            $matchedIds = [];
            foreach (array_values($durArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }
            $durModels = Dur::with('datasetVersions')->whereIn('id', $matchedIds)->where('status', 'ACTIVE')->get();

            foreach ($durArray as $i => $dur) {
                $foundFlag = false;
                foreach ($durModels as $model) {
                    if ((int)$dur['_id'] === $model['id']) {
                        $datasetTitles = $this->durDatasetTitles($model);
                        $durArray[$i]['_source']['created_at'] = $model['created_at'];
                        $durArray[$i]['_source']['updated_at'] = $model['updated_at'];
                        $durArray[$i]['projectTitle'] = $model['project_title'];
                        $durArray[$i]['organisationName'] = $model['organisation_name'];
                        $durArray[$i]['team'] = $model['team'];
                        $durArray[$i]['mongoObjectId'] = $model['mongo_object_id']; // remove
                        $durArray[$i]['datasetTitles'] = array_column($datasetTitles, 'title');
                        $durArray[$i]['datasetIds'] = array_column($datasetTitles, 'id');
                        $durArray[$i]['dataProviderColl'] = $this->getDataProviderColl($model->toArray());
                        $durArray[$i]['toolNames'] = $this->durToolNames($model['id']);
                        $durArray[$i]['non_gateway_datasets'] = $model['non_gateway_datasets'];
                        $durArray[$i]['collectionNames'] = $this->getCollectionNamesByDurId($model['id']);

                        $foundFlag = true;
                        break;
                    }
                }
                if (!$foundFlag) {
                    unset($durArray[$i]);
                    continue;
                }
            }
            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search dur export data',
                ]);
                return Excel::download(new DataUseExport($durArray), 'dur.csv');
            }

            $durArray = $this->sortSearchResult($durArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $durArray, $perPage);
            unset($durArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids' => $matchedIds,
            ]);

            $final = $aggs->merge($paginatedData);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => "Search dur",
            ]);

            return response()->json($final, 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

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
        try {
            $input = $request->all();

            $download = array_key_exists('download', $input) ? $input['download'] : false;
            $sort = $request->query('sort', 'score:desc');

            $tmp = explode(":", $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $source = !is_null($input['source']) ? $input['source'] : 'GAT';

            $matchedIds = null;
            if ($source === 'GAT') {
                $aggs = Filter::where('type', 'paper')->get()->toArray();
                $input['aggs'] = $aggs;

                try {
                    $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/publications';
                    $response = Http::post($urlString, $input);
                } catch (ConnectionException $e) {
                    Auditor::log([
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $e->getMessage(),
                    ]);
                    throw new Exception('Operation timeout: The search query is too long. Please try searching with fewer keywords');
                } catch (Exception $e) {
                    Auditor::log([
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $e->getMessage(),
                    ]);
                    throw new Exception($e->getMessage());
                }

                $pubArray = $response['hits']['hits'];
                $totalResults = $response['hits']['total']['value'];
                $matchedIds = [];
                foreach (array_values($pubArray) as $i => $d) {
                    $matchedIds[] = $d['_id'];
                }

                $pubModels = Publication::whereIn('id', $matchedIds)->get();

                foreach ($pubArray as $i => $p) {
                    $foundFlag = false;
                    foreach ($pubModels as $model) {
                        if ((int)$p['_id'] !== $model['id']) {
                            continue;
                        } else {
                            $pubArray[$i]['_source']['created_at'] = $model['created_at'];
                            $pubArray[$i]['_source']['year_of_publication'] = $model['year_of_publication'];
                            $pubArray[$i]['paper_title'] = $model['paper_title'];
                            // This is an in-transit workaround to remove header elements of the abstract text in the request.
                            // This requires more thought, and only a temporary fix. LS.
                            $pubArray[$i]['abstract'] = preg_replace('/<h4>(.*?)<\/h4>/', '', $model['abstract']);
                            $pubArray[$i]['authors'] = $model['authors'];
                            $pubArray[$i]['journal_name'] = $model['journal_name'];
                            $pubArray[$i]['year_of_publication'] = $model['year_of_publication'];
                            $pubArray[$i]['full_text_url'] = 'https://doi.org/' . $model['paper_doi'];
                            $pubArray[$i]['url'] = $model['url'];
                            $pubArray[$i]['publication_type'] = $model['publication_type'];

                            $datasets = $this->getDatasetByPublicationId($model['id']);
                            $pubArray[$i]['datasetLinkTypes'] = $datasets['datasetLinkTypes'];
                            $pubArray[$i]['datasetVersions'] = $datasets['datasetVersions'];

                            $pubArray[$i]['collections'] = $this->getCollectionsByPublicationId($model['id']);
                            $pubArray[$i]['tools'] = $this->getToolsByPublicationId($model['id']);
                            $pubArray[$i]['durs'] = $this->getDursByPublicationId($model['id']);


                            $foundFlag = true;
                            break;
                        }
                    }
                    if (!$foundFlag) {
                        unset($pubArray[$i]);
                        continue;
                    }
                }
            } else {
                if (isset($input['query']) && is_array($input['query'])) {
                    $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/federated_papers/field_search/array';
                } else {
                    if (isset($input['query']) && $this->isDoi($input['query'])) {
                        $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/federated_papers/doi';
                    } else {
                        $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/federated_papers/field_search';
                    }
                }
                $input['field'] = ['TITLE', 'ABSTRACT', 'METHODS'];

                try {
                    $response = Http::post($urlString, $input);
                } catch (ConnectionException $e) {
                    Auditor::log([
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $e->getMessage(),
                    ]);
                    throw new Exception('Operation timeout: The search query is too long. Please try searching with fewer keywords');
                } catch (Exception $e) {
                    Auditor::log([
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $e->getMessage(),
                    ]);
                    throw new Exception($e->getMessage());
                }

                $pubArray = $response['resultList']['result'] ?? [];
                $totalResults = $response['hitCount'];
                foreach ($pubArray as $i => $paper) {
                    $pubArray[$i]['testid'] = $paper;
                    $pubArray[$i]['_source']['year_of_publication'] = $paper['pubYear'];
                    $pubArray[$i]['_source']['title'] = $paper['title'];
                    $pubArray[$i]['paper_title'] = $paper['title'];
                    // This is an in-transit workaround to remove header elements of the abstract text in the request.
                    // This requires more thought, and only a temporary fix. LS.
                    $pubArray[$i]['abstract'] = preg_replace('/<h4>(.*?)<\/h4>/', '', $paper['abstractText']);
                    $pubArray[$i]['authors'] = $paper['authorString'];
                    $pubArray[$i]['journal_name'] = isset($paper['journalInfo']) ?
                        $paper['journalInfo']['journal']['title'] : '';
                    $pubArray[$i]['year_of_publication'] = $paper['pubYear'];
                    $pubArray[$i]['full_text_url'] = $paper['fullTextUrlList']['fullTextUrl'][0]['url'];
                    $pubArray[$i]['url'] = $paper['fullTextUrlList']['fullTextUrl'][0]['url'];
                }
            }

            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => "Search publications export data",
                ]);
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

            throw new Exception($e->getMessage());
        }
    }

    private function getDatasetByPublicationId(int $publicationId)
    {
        $linkTypeMappings = [
            'USING' => 'Using a dataset',
            'ABOUT' => 'About a dataset',
            'UNKNOWN' => 'Unknown',
        ];
        $arrDatasetLinkTypes = [];
        $arrDatasetVersions = [];

        $publicationHasDatasetVersions = PublicationHasDatasetVersion::where([
            'publication_id' => $publicationId,
        ])->select(['dataset_version_id', 'link_type'])->get();

        foreach ($publicationHasDatasetVersions as $publicationHasDatasetVersion) {
            $datasetVersions = DatasetVersion::where([
                'id' => $publicationHasDatasetVersion->dataset_version_id,
            ])->select(['id', 'dataset_id'])->first();
            if (is_null($datasetVersions)) {
                continue;
            }

            $datasetById = Dataset::where([
                'id' => $datasetVersions->dataset_id,
                'status' => Dataset::STATUS_ACTIVE,
            ])->select('id')->first();
            if (is_null($datasetById)) {
                continue;
            }

            $metadata = $datasetById->latestVersion()->metadata;
            $arrDatasetVersions[] = [
                'id' => $datasetVersions->id,
                'dataset_id' => $datasetVersions->dataset_id,
                'name' => $metadata['metadata']['summary']['shortTitle'],
            ];
            $arrDatasetLinkTypes[]  = $linkTypeMappings[$publicationHasDatasetVersion->link_type ?? 'UNKNOWN'];
        }

        return [
            'datasetLinkTypes' => array_unique($arrDatasetLinkTypes),
            'datasetVersions' => $arrDatasetVersions,
        ];
    }

    private function getCollectionsByPublicationId(int $publicationId)
    {
        $collectionHasPublications = CollectionHasPublication::where([
            'publication_id' => $publicationId
        ])->select('collection_id')->get();
        $collectionIds = convertArrayToArrayWithKeyName($collectionHasPublications, 'collection_id');
        if (!count($collectionIds)) {
            return [];
        }

        return Collection::whereIn('id', $collectionIds)->where('status', 'ACTIVE')->select(['id', 'name', 'description'])->get();
    }

    private function getToolsByPublicationId(int $publicationId)
    {
        $publicationHasTools = PublicationHasTool::where([
            'publication_id' => $publicationId
        ])->select('tool_id')->get();
        $toolIds = convertArrayToArrayWithKeyName($publicationHasTools, 'tool_id');
        if (!count($toolIds)) {
            return [];
        }

        return Tool::whereId('id', $toolIds)->where('status', 'ACTIVE')->select(['id', 'name', 'description'])->get();
    }

    private function getDursByPublicationId(int $publicationId)
    {
        $durHasPublications = DurHasPublication::where([
            'publication_id' => $publicationId
        ])->select('dur_id')->get();
        $durIds = convertArrayToArrayWithKeyName($durHasPublications, 'dur_id');
        if (!count($durIds)) {
            return [];
        }

        return Dur::whereId('id', $durIds)->where('status', 'ACTIVE')->select(['id', 'project_title'])->get();
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
        try {
            $input = $request->all();

            $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/federated_papers/doi';
            $response = Http::post($urlString, $input);

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
            } else {
                return response()->noContent();
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => "Search for publication by doi",
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $pubResult
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/search/data_provider_colls",
     *      summary="Keyword search across gateway data providers",
     *      description="Returns gateway data provider colls related to the provided query term(s)",
     *      tags={"Search-DataProviderColls"},
     *      summary="Search@data_provider_colls",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="national data provider colls"),
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
    public function dataProviderColls(Request $request): JsonResponse|BinaryFileResponse
    {
        try {
            $input = $request->all();
            $download = array_key_exists('download', $input) ? $input['download'] : false;
            $sort = $request->query('sort', 'score:desc');

            $tmp = explode(":", $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $aggs = Filter::where('type', 'dataProviderColl')->get()->toArray();
            $input['aggs'] = $aggs;

            $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/data_custodian_networks';
            $response = Http::post($urlString, $input);

            $dataProviderCollArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = [];
            foreach (array_values($dataProviderCollArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $dataProviderCollModels = DataProviderColl::whereIn('id', $matchedIds)->with('teams')->get();

            foreach ($dataProviderCollArray as $i => $dp) {
                $foundFlag = false;
                foreach ($dataProviderCollModels as $model) {
                    if ((int)$dp['_id'] === $model['id']) {
                        $dataProviderCollArray[$i]['id'] = $model['id'];
                        $dataProviderCollArray[$i]['_source']['updated_at'] = $model['updated_at'];
                        $dataProviderCollArray[$i]['name'] = $model['name'];
                        $dataProviderCollArray[$i]['img_url'] =  (is_null($model['img_url']) || strlen(trim($model['img_url'])) === 0 || (preg_match('/^https?:\/\//', $model['img_url'])) ? null : Config::get('services.media.base_url') . $model['img_url']);
                        $dataProviderCollArray[$i]['datasetTitles'] = $this->dataProviderDatasetTitles($model);
                        $dataProviderCollArray[$i]['geographicLocations'] = $this->dataProviderLocations($model);
                        $foundFlag = true;
                        break;
                    }
                }
                if (!$foundFlag) {
                    unset($dataProviderCollArray[$i]);
                    continue;
                }
            }

            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search data provider export data',
                ]);
                return Excel::download(new DataProviderCollExport($dataProviderCollArray), 'dataProviderColl.csv');
            }

            $dataProviderCollArray = $this->sortSearchResult($dataProviderCollArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $dataProviderCollArray, $perPage);
            unset($dataProviderCollArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
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

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/search/data_providers",
     *      summary="Keyword search across gateway data providers",
     *      description="Returns gateway data providers related to the provided query term(s)",
     *      tags={"Search-DataProviders"},
     *      summary="Search@data_providers",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="national data providers"),
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
    public function dataProviders(Request $request): JsonResponse|BinaryFileResponse
    {
        try {
            $input = $request->all();
            $download = array_key_exists('download', $input) ? $input['download'] : false;
            $sort = $request->query('sort', 'score:desc');

            $tmp = explode(":", $sort);
            $sortField = $tmp[0];

            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $aggs = Filter::where('type', 'dataProvider')->get()->toArray();
            $input['aggs'] = $aggs;

            $urlString = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/data_providers';
            $response = Http::post($urlString, $input);

            $dataProviderArray = $response['hits']['hits'];
            $totalResults = $response['hits']['total']['value'];
            $matchedIds = [];
            foreach (array_values($dataProviderArray) as $i => $d) {
                $matchedIds[] = $d['_id'];
            }

            $dataProviderModels = Team::whereIn('id', $matchedIds)->get();

            foreach ($dataProviderArray as $i => $dp) {
                $foundFlag = false;
                foreach ($dataProviderModels as $model) {
                    if ((int)$dp['_id'] === $model['id']) {
                        $dataProviderArray[$i]['_source']['updated_at'] = $model['updated_at'];
                        $dataProviderArray[$i]['name'] = $model['name'];
                        $dataProviderArray[$i]['team_logo'] = (is_null($model['team_logo']) || strlen(trim($model['team_logo'])) === 0) ? '' : (preg_match('/^https?:\/\//', $model['team_logo']) ? $model['team_logo'] : Config::get('services.media.base_url') . $model['team_logo']);
                        $foundFlag = true;
                        break;
                    }
                }
                if (!$foundFlag) {
                    unset($dataProviderArray[$i]);
                    continue;
                }
            }

            if ($download) {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Search data provider export data',
                ]);
                return Excel::download(new DataProviderExport($dataProviderArray), 'dataProvider.csv');
            }

            $dataProviderArray = $this->sortSearchResult($dataProviderArray, $sortField, $sortDirection);

            $perPage = request('per_page', Config::get('constants.per_page'));
            $paginatedData = $this->paginateArray($request, $dataProviderArray, $perPage);
            unset($dataProviderArray);

            $aggs = collect([
                'aggregations' => $response['aggregations'],
                'elastic_total' => $totalResults,
                'ids' => $matchedIds,
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

        if (empty($durMatch['datasetVersions']) || !is_iterable($durMatch['datasetVersions'])) {
            return [];
        }

        $datasetVersionIds = collect($durMatch['datasetVersions'])->select('dataset_version_id')->toArray();

        $datasets = DatasetVersion::whereIn('id', $datasetVersionIds)
        ->get();

        $datasetTitles = [];

        foreach ($datasets as $dataset) {
            $datasetTitles[] = [
                'title' => $dataset['metadata']['metadata']['summary']['shortTitle'],
                'id' => $dataset['id'],
            ];

        }

        usort($datasetTitles, function ($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

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
                function ($a, $b) use ($sortField) {
                    return $a['_source'][$sortField] <=> $b['_source'][$sortField];
                }
            );
        } else {
            usort(
                $resultArray,
                function ($a, $b) use ($sortField) {
                    if (is_string($b['_source'][$sortField])) {
                        return strtoupper($b['_source'][$sortField]) <=> strtoupper($a['_source'][$sortField]);
                    } else {
                        return $b['_source'][$sortField] <=> $a['_source'][$sortField];
                    }
                }
            );
        }
        return $resultArray;
    }

    private function trimPayload(array &$input): array
    {
        $miniMetadata = $input['metadata'];

        $materialTypes = $this->getMaterialTypes($input);
        $containsTissue = $this->getContainsTissues($materialTypes);
        $hasTechnicalMetadata = (bool) count($this->getValueByPossibleKeys($input, ['metadata.structuralMetadata'], []));

        $accessServiceCategory = null;
        if (array_key_exists('accessServiceCategory', $miniMetadata['accessibility']['access'])) {
            $accessServiceCategory  = $miniMetadata['accessibility']['access']['accessServiceCategory'];
        }

        $minimumKeys = [
            'summary',
            'provenance',
            'accessibility',
        ];

        foreach ($miniMetadata as $key => $value) {
            if (!in_array($key, $minimumKeys)) {
                unset($miniMetadata[$key]);
            }
        }

        $miniMetadata['additional']['containsTissue'] = $containsTissue;
        $miniMetadata['accessibility']['access']['accessServiceCategory'] = $accessServiceCategory;
        $miniMetadata['additional']['hasTechnicalMetadata'] = $hasTechnicalMetadata;

        return $miniMetadata;
    }

    private function getDatasetTitle(array $datasetIds): array
    {
        $response = [];

        foreach ($datasetIds as $datasetId) {
            $metadata = Dataset::where(['id' => $datasetId])
                ->first()
                ->latestVersion()
                ->metadata;
            $response[] = $metadata['metadata']['summary']['shortTitle'];
        }

        usort($response, 'strcasecmp');
        return $response;
    }

    private function getDataProviderColl(array $model): array
    {
        $dataProviderCollId = DataProviderCollHasTeam::where('team_id', $model['team_id'])
            ->pluck('data_provider_coll_id')
            ->all();

        $dataProviderColl = DataProviderColl::whereIn('id', $dataProviderCollId)
            ->pluck('name')
            ->all();

        return $dataProviderColl;
    }

    private function dataProviderDatasetTitles(DataProviderColl $provider): array
    {
        $datasetTitles = array();
        foreach ($provider['teams'] as $team) {
            $datasets = Dataset::where([
                'team_id' => $team['id'],
                'status' => 'ACTIVE',
                ])->with(['versions:id,dataset_id,short_title'])->select(['id'])->get();
            foreach ($datasets as $dataset) {
                $metadata = $dataset['versions'][0];
                $datasetTitles[] = $metadata['short_title'];
            }
        }
        usort($datasetTitles, 'strcasecmp');
        return $datasetTitles;
    }

    private function dataProviderLocations(DataProviderColl $provider): array
    {
        $locations = array();
        foreach ($provider['teams'] as $team) {
            $datasets = Dataset::where('team_id', $team['id'])->get();
            foreach ($datasets as $dataset) {
                $spatialCoverage = $dataset->allSpatialCoverages;
                foreach ($spatialCoverage as $loc) {
                    if (!in_array($loc['region'], $locations)) {
                        $locations[] = $loc['region'];
                    }
                }
            }
        }
        return $locations;
    }

    private function durToolNames(int $durId): array
    {
        $toolNames = [];

        $toolIds = DurHasTool::where('dur_id', $durId)->pluck('tool_id')->all();
        if (!count($toolIds)) {
            return [];
        }

        $toolNames = Tool::whereIn('id', $toolIds)->pluck('name')->all();

        return $toolNames;
    }

    private function toolDurTitles(int $toolId): array
    {
        $durNames = [];

        $durIds = DurHasTool::where('tool_id', $toolId)->pluck('dur_id')->all();
        if (!count($durIds)) {
            return [];
        }

        $durNames = Dur::whereIn('id', $durIds)->where('status', 'ACTIVE')->pluck('project_title')->all();

        return $durNames;
    }

    private function isDoi(string $query): bool
    {
        $pattern = '/10.\d{4,9}[-._;()\/:a-zA-Z0-9]+(?=[\s,\/]|$)/i';
        return (bool) preg_match($pattern, $query);
    }
}
