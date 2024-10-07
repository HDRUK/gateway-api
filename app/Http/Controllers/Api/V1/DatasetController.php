<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Jobs\TermExtraction;
use App\Http\Traits\AddMetadataVersion;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Traits\IndexElastic;
use App\Http\Traits\MetadataOnboard;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use MetadataManagementController as MMC;
use App\Http\Requests\Dataset\GetDataset;
use App\Http\Requests\Dataset\EditDataset;
use App\Http\Requests\Dataset\CreateDataset;
use App\Http\Requests\Dataset\DeleteDataset;
use App\Http\Requests\Dataset\UpdateDataset;
use App\Exports\DatasetStructuralMetadataExport;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatasetController extends Controller
{
    use AddMetadataVersion;
    use IndexElastic;
    use GetValueByPossibleKeys;
    use MetadataOnboard;

    /**
     * @OA\Get(
     *    path="/api/v1/datasets",
     *    operationId="fetch_all_datasets",
     *    tags={"Datasets"},
     *    summary="DatasetController@index",
     *    description="Get All Datasets",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="pid",
     *       in="query",
     *       description="get based on a pid",
     *       required=false,
     *       example="aa588d1c-21e7-42d9-9b60-48e3d6b784a9",
     *       @OA\Schema(
     *          type="string",
     *          description="retrieve based on pid",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       description="Field and direction (colon separated) to sort by (default: 'created:desc') ... <br/> <br/>
        - ?sort=\<field\>:\<direction\> <br/>
        - \<direction\> can only be 'asc' or 'desc'  <br/>
        - \<field\> can only be a valid field for the dataset table that can be ordered on  <br/>
        - \<field\> can start with the prefix 'metadata.' so that nested values within the field 'metadata'  <br/>
            (represented by the GWDM JSON structure) can be used to order on.  <br/>  <br/>",
     *       example="created:desc",
     *       @OA\Schema(
     *          type="string",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="title",
     *       in="query",
     *       description="Three or more characters to filter dataset titles by",
     *       example="hdr",
     *       @OA\Schema(
     *          type="string",
     *          description="Three or more characters to filter dataset titles by",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="status",
     *       in="query",
     *       description="Dataset status to filter by ('ACTIVE', 'DRAFT', 'ARCHIVED')",
     *       example="ACTIVE",
     *       @OA\Schema(
     *          type="string",
     *          description="Dataset status to filter by",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          )
     *       )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $matches = [];
            $teamId = $request->query('team_id', null);
            $filterStatus = $request->query('status', null);
            $datasetId = $request->query('dataset_id', null);
            $mongoPId = $request->query('mongo_pid', null);
            $withMetadata = $request->boolean('with_metadata', true);

            $sort = $request->query('sort', 'created:desc');

            $tmp = explode(":", $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $sortOnMetadata = str_starts_with($sortField, 'metadata.');

            $allFields = collect(Dataset::first())->keys()->toArray();
            if (!$sortOnMetadata && count($allFields) > 0 && !in_array($sortField, $allFields)) {
                return response()->json([
                    'message' => '\"' . $sortField .'\" is not a valid field to sort on'
                ], 400);
            }

            $validDirections = ['desc', 'asc'];

            if (!in_array($sortDirection, $validDirections)) {
                //if the sort direction is not desc or asc then return a bad request
                return response()->json([
                    "message" => 'Sort direction must be either: ' .
                        implode(' OR ', $validDirections) .
                        '. Not "' . $sortDirection .'"'
                    ], 400);
            }

            // apply any initial filters to get initial datasets
            $filterTitle = $request->query('title', null);

            $initialDatasets = Dataset::when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })->when($datasetId, function ($query) use ($datasetId) {
                return $query->where('datasetid', '=', $datasetId);
            })->when($mongoPId, function ($query) use ($mongoPId) {
                return $query->where('mongo_pid', '=', $mongoPId);
            })->when(
                $request->has('withTrashed') || $filterStatus === 'ARCHIVED',
                function ($query) {
                    return $query->withTrashed();
                }
            )->when(
                $filterStatus,
                function ($query) use ($filterStatus) {
                    return $query->where('status', '=', $filterStatus);
                }
            )->select(['id', 'updated'])->get();

            // Map initially found datasets to just ids.
            foreach ($initialDatasets as $ds) {
                $matches[] = $ds->id;
            }

            if (!empty($filterTitle)) {
                // If we've received a 'title' for the search, then only return
                // datasets that match that title
                $titleMatches = [];

                // For each of the initially found datasets matching previous
                // filters and refine further on textual based matches.
                foreach ($matches as $m) {
                    $version = DatasetVersion::where('dataset_id', $m)
                    ->filterTitle($filterTitle)
                    ->select('dataset_id')
                    ->when(
                        $request->has('withTrashed') || $filterStatus === 'ARCHIVED',
                        function ($query) {
                            return $query->withTrashed();
                        }
                    )->first();

                    if ($version) {
                        $titleMatches[] = $version->dataset_id;
                    }
                }

                // Finally intersect our two arrays to find commonality between all
                // filtering methods. This will return a much slimmer array of returned
                // items
                $matches = array_intersect($matches, $titleMatches);
            }

            $perPage = request('per_page', Config::get('constants.per_page'));

            // perform query for the matching datasets with ordering and pagination.
            // Include soft-deleted versions.
            $datasets = Dataset::whereIn('id', $matches)
                ->when($withMetadata, fn ($query) => $query->with('latestMetadata'))
                ->when(
                    $request->has('withTrashed') || $filterStatus === 'ARCHIVED',
                    function ($query) {
                        return $query->withTrashed();
                    }
                )
                ->when(
                    $sortOnMetadata,
                    fn ($query) => $query->orderByMetadata($sortField, $sortDirection),
                    fn ($query) => $query->orderBy($sortField, $sortDirection)
                )
                ->paginate($perPage, ['*'], 'page');

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dataset get all',
            ]);

            return response()->json(
                $datasets
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }


    /**
     * @OA\Get(
     *    path="/api/v1/datasets/count/{field}",
     *    operationId="count_unique_fields",
     *    tags={"Datasets"},
     *    summary="DatasetController@count",
     *    description="Get Counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="field",
     *       in="path",
     *       description="name of the field to perform a count on",
     *       required=true,
     *       example="status",
     *       @OA\Schema(
     *          type="string",
     *          description="status field",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="object",
     *          )
     *       )
     *    )
     * )
     */
    public function count(Request $request, string $field): JsonResponse
    {
        try {
            $teamId = $request->query('team_id', null);
            $counts = Dataset::when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })->withTrashed()
                ->select($field)
                ->get()
                ->groupBy($field)
                ->map->count();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dataset count',
            ]);

            return response()->json([
                "data" => $counts
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/datasets/{id}",
     *    operationId="fetch_datasets",
     *    tags={"Datasets"},
     *    summary="DatasetController@show",
     *    description="Get dataset by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="dataset id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="dataset id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="export",
     *       in="query",
     *       description="Alternative output schema model.",
     *       @OA\Schema(type="string", example="structuralMetadata")
     *    ),
      *    @OA\Parameter(
     *       name="schema_model",
     *       in="query",
     *       description="Alternative output schema model.",
     *       @OA\Schema(type="string")
     *    ),
     *    @OA\Parameter(
     *       name="schema_version",
     *       in="query",
     *       description="Alternative output schema version.",
     *       @OA\Schema(type="string")
     *    ),
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
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     *
     */
    public function show(GetDataset $request, int $id): JsonResponse|BinaryFileResponse
    {
        try {
            $exportStructuralMetadata = $request->query('export', null);

            // Retrieve the dataset with collections, publications, and counts
            $dataset = Dataset::with("team")->find($id);

            if (!$dataset) {
                return response()->json(['message' => 'Dataset not found'], 404);
            }

            // Inject attributes via the dataset version table
            // notes Calum 12th August 2024...
            // - This is a mess.. why is `pulibcations_count` returning something different than dataset->allPublications??
            // - Tools linakge not returned
            // - For the FE i just need a tools linkage count so i'm gonna return `count(dataset->allTools)` for now
            // - Same for collections
            // - Leaving this as it is as im not 100% sure what any FE knock-on effect would be
            $dataset->setAttribute('durs_count', $dataset->latestVersion()->durHasDatasetVersions()->count());
            $dataset->setAttribute('publications_count', $dataset->latestVersion()->publicationHasDatasetVersions()->count());
            $dataset->setAttribute('tools_count', count($dataset->allTools));
            $dataset->setAttribute('collections_count', count($dataset->allCollections));
            $dataset->setAttribute('spatialCoverage', $dataset->allSpatialCoverages  ?? []);
            $dataset->setAttribute('durs', $dataset->allDurs  ?? []);
            $dataset->setAttribute('publications', $dataset->allPublications  ?? []);
            $dataset->setAttribute('named_entities', $dataset->allNamedEntities  ?? []);
            $dataset->setAttribute('collections', $dataset->allCollections  ?? []);

            $outputSchemaModel = $request->query('schema_model');
            $outputSchemaModelVersion = $request->query('schema_version');

            // Retrieve the latest version
            $latestVersion = $dataset->latestVersion();

            // Return the latest metadata for this dataset
            if (!($outputSchemaModel && $outputSchemaModelVersion)) {
                $withLinks = DatasetVersion::where('id', $latestVersion['id'])
                    ->with(['linkedDatasetVersions'])
                    ->first();
                if ($withLinks) {
                    $dataset->setAttribute('versions', [$withLinks]);
                }
            }

            if ($outputSchemaModel && $outputSchemaModelVersion) {
                $translated = MMC::translateDataModelType(
                    json_encode($latestVersion->metadata),
                    $outputSchemaModel,
                    $outputSchemaModelVersion,
                    Config::get('metadata.GWDM.name'),
                    Config::get('metadata.GWDM.version'),
                );

                if ($translated['wasTranslated']) {
                    $withLinks = DatasetVersion::where('id', $latestVersion['id'])
                        ->with(['linkedDatasetVersions'])
                        ->first();
                    $withLinks['metadata'] = json_encode(['metadata' => $translated['metadata']]);
                    $dataset->setAttribute('versions', [$withLinks]);
                } else {
                    return response()->json([
                        'message' => 'failed to translate',
                        'details' => $translated
                    ], 400);
                }
            } elseif ($outputSchemaModel) {
                throw new Exception('You have given a schema_model but not a schema_version');
            } elseif ($outputSchemaModelVersion) {
                throw new Exception('You have given a schema_version but not schema_model');
            }

            if ($exportStructuralMetadata === 'structuralMetadata') {
                $arrayDataset = $dataset->toArray();
                $latestVersionId = $latestVersion->id;
                $versions = $this->getValueByPossibleKeys($arrayDataset, ['versions'], []);

                $count = 0;
                if (count($versions)) {
                    foreach ($versions as $version) {
                        if ((int) $version['id'] === (int) $latestVersionId) {
                            break;
                        }
                        $count++;
                    }
                }
                $export = count($versions) ? $this->getValueByPossibleKeys($arrayDataset, ['versions.' . $count . '.metadata.metadata.structuralMetadata'], []) : [];

                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => 'Dataset get ' . $id . ' download structural metadata',
                ]);

                return Excel::download(new DatasetStructuralMetadataExport($export), 'dataset-structural-metadata.csv');
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dataset get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $dataset,
            ], 200);

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/datasets",
     *    operationId="create_datasets",
     *    tags={"Datasets"},
     *    summary="DatasetController@store",
     *    description="Create a new dataset",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="team_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="3"),
     *             @OA\Property(property="create_origin", type="string", example="MANUAL"),
     *             @OA\Property(property="mongo_object_id", type="string", example="abc123"),
     *             @OA\Property(property="mongo_id", type="string", example="456"),
     *             @OA\Property(property="mongo_pid", type="string", example="def789"),
     *             @OA\Property(property="datasetid", type="string", example="xyz1011"),
     *             @OA\Property(property="metadata", type="array", @OA\Items())
     *          )
     *       )
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Created",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="integer", example="100")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function store(CreateDataset $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $elasticIndexing = $request->boolean('elastic_indexing', true);
            $teamId = (int)$input['team_id'];

            $team = Team::where('id', $teamId)->first()->toArray();

            $input['metadata'] = $this->extractMetadata($input['metadata']);

            $inputSchema = $request->query('input_schema', null);
            $inputVersion = $request->query('input_version', null);

            // Ensure title is present for creating a dataset
            if (empty($input['metadata']['metadata']['summary']['title'])) {
                return response()->json([
                    'message' => 'Title is required to save a dataset',
                ], 400);
            }

            $metadataResult = $this->metadataOnboard(
                $input,
                $team,
                $inputSchema,
                $inputVersion,
                $elasticIndexing
            );

            if ($metadataResult['translated']) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'team_id' => $team['id'],
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Dataset ' . $metadataResult['dataset_id'] . ' with version ' .
                        $metadataResult['version_id'] . ' created',
                ]);

                return response()->json([
                    'message' => 'created',
                    'data' => $metadataResult['dataset_id'],
                    'version' => $metadataResult['version_id'],
                ], 201);
            } else {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed',
                    'details' => $metadataResult['response'],
                ], 400);
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/datasets/{id}",
     *    operationId="update_datasets",
     *    tags={"Datasets"},
     *    summary="DatasetController@update",
     *    description="Update a dataset with a new dataset version",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="dataset id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="dataset id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="team_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="3"),
     *             @OA\Property(property="create_origin", type="string", example="MANUAL"),
     *             @OA\Property(property="metadata", type="array", @OA\Items())
     *          )
     *       )
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Created",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="integer", example="100")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function update(UpdateDataset $request, int $id)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $elasticIndexing = $request->boolean('elastic_indexing', true);
            $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ? $input['is_cohort_discovery'] : false;

            $teamId = (int)$input['team_id'];
            $userId = (int)$input['user_id'];

            $user = User::where('id', $userId)->first();
            $team = Team::where('id', $teamId)->first();
            $currDataset = Dataset::where('id', $id)->first();
            $currentPid = $currDataset->pid;


            $payload = $this->extractMetadata($input['metadata']);
            $payload['extra'] = [
                "id" => $id,
                "pid" => $currentPid,
                "datasetType" => "Health and disease",
                "publisherId" => $team['pid'],
                "publisherName" => $team['name']
            ];

            $inputSchema = isset($input['metadata']['schemaModel']) ? $input['metadata']['schemaModel'] : null;
            $inputVersion = isset($input['metadata']['schemaVersion']) ? $input['metadata']['schemaVersion'] : null;

            $submittedMetadata = $input['metadata']['metadata'];
            $gwdmMetadata = null;

            $traserResponse = MMC::translateDataModelType(
                json_encode($payload),
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version'),
                $inputSchema, //user can force an input version to avoid traser unknown errors
                $inputVersion, // as above
                $request['status'] !== Dataset::STATUS_DRAFT, // Disable input validation if it's a draft
                $request['status'] !== Dataset::STATUS_DRAFT // Disable output validation if it's a draft
            );
            if ($traserResponse['wasTranslated']) {
                //set the gwdm metadata
                $gwdmMetadata = $traserResponse['metadata'];
            } else {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed.',
                    'details' => $traserResponse,
                ], 400);
            }

            // Update the existing dataset parent record with incoming data
            $updateTime = now();
            $currDataset->update([
                'user_id' => $input['user_id'],
                'team_id' => $input['team_id'],
                'updated' => $updateTime,
                'pid' => $currentPid,
                'create_origin' => $input['create_origin'],
                'status' => $request['status'],
                'is_cohort_discovery' => $isCohortDiscovery,
            ]);

            $versionNumber = $currDataset->lastMetadataVersionNumber()->version;

            $datsetVersionId = $this->addMetadataVersion(
                $currDataset,
                $request['status'],
                $updateTime,
                $gwdmMetadata,
                $submittedMetadata
            );

            $versionNumber = $currDataset->lastMetadataVersionNumber()->version;
            // Dispatch term extraction to a subprocess if the dataset moves from draft to active
            if($request['status'] === Dataset::STATUS_ACTIVE &&  Config::get('ted.enabled')) {

                $tedData = Config::get('ted.use_partial') ? $input['metadata']['metadata']['summary'] : $input['metadata']['metadata'];

                TermExtraction::dispatch(
                    $currDataset->id,
                    $datsetVersionId,
                    $versionNumber,
                    base64_encode(gzcompress(gzencode(json_encode($tedData), 6))),
                    $elasticIndexing,
                    Config::get('ted.use_partial')
                );
            }

            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' with version ' . ($versionNumber + 1) . ' updated',
            ]);

            //note Calum 13/08/2024
            // - ive removed returning the data because i dont know what the hell is going on with
            //   the show() method and 'withLinks' etc. etc. [see above]
            // - i think its safe that the PUT method doesnt try to return the updated data
            // - GET /dataset/{id} should be used to get the latest dataset and metadata
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
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

    /**
     * @OA\Patch(
     *    path="/api/v1/datasets/{id}",
     *    operationId="patch_datasets",
     *    tags={"Datasets"},
     *    summary="DatasetController@edit",
     *    description="Patch dataset by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="unarchive",
     *       in="query",
     *       description="Unarchive a dataset",
     *       @OA\Schema(
     *          type="string",
     *          description="instruction to unarchive dataset",
     *       ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     *  )
     */
    public function edit(EditDataset $request, int $id)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            if ($request->has('unarchive')) {
                $datasetModel = Dataset::withTrashed() ->where(['id' => $id]) ->first();

                if (in_array($request['status'], [Dataset::STATUS_ACTIVE, Dataset::STATUS_DRAFT])) {
                    $datasetModel->status = $request['status'];
                    $datasetModel->deleted_at = null;
                    $datasetModel->save();

                    $metadata = DatasetVersion::withTrashed()->where('dataset_id', $id)->latest()->first();
                    $metadata->deleted_at = null;
                    $metadata->save();

                    if ($request['status'] === Dataset::STATUS_ACTIVE) {
                        $this->reindexElastic($id);
                    }

                    Auditor::log([
                        'user_id' => (int)$jwtUser['id'],
                        'team_id' => $datasetModel['team_id'],
                        'action_type' => 'UPDATE',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => 'Dataset ' . $id . ' marked as ' . strtoupper($request['status']) . ' updated',
                    ]);
                } else {
                    $message = 'unknown status type';

                    Auditor::log([
                        'user_id' => (int)$jwtUser['id'],
                        'team_id' => $datasetModel['team_id'],
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $message,
                    ]);

                    throw new Exception($message);
                }
            } else {
                $datasetModel = Dataset::where(['id' => $id])->first();

                if ($datasetModel['status'] === Dataset::STATUS_ARCHIVED) {
                    return response()->json([
                        'message' => 'status of an archived Dataset cannot be modified',
                    ], 400);
                }

                if (in_array($request['status'], [
                    Dataset::STATUS_ACTIVE, Dataset::STATUS_DRAFT
                ])) {
                    $datasetModel->status = $request['status'];
                    $datasetModel->save();
                } else {
                    $message = 'unknown status type';

                    Auditor::log([
                        'user_id' => (int)$jwtUser['id'],
                        'team_id' => $datasetModel['team_id'],
                        'action_type' => 'EXCEPTION',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => $message,
                    ]);

                    throw new Exception($message);
                }

                // TODO remaining edit steps e.g. if dataset appears in the request
                // body validate, translate if needed, update Mauro data model, etc.

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'team_id' => $datasetModel['team_id'],
                    'action_type' => 'UPDATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Dataset ' . $id . ' marked as ' .
                        strtoupper($request['status']) . ' updated',
                ]);
            }

            return response()->json([
                'message' => 'success'
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/datasets/{id}",
     *      operationId="delete_datasets",
     *      summary="Delete a dataset",
     *      description="Delete a dataset",
     *      tags={"Datasets"},
     *      summary="DatasetController@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="dataset id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="dataset id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *           ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function destroy(DeleteDataset $request, string $id) // softdelete
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $dataset = Dataset::where('id', $id)->first();
            $deleteFromElastic = ($dataset->status === Dataset::STATUS_ACTIVE);

            MMC::deleteDataset($id);

            if ($deleteFromElastic) {
                $this->deleteDatasetFromElastic($id);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    public function destroyByPid(Request $request, string $pid) // softdelete
    {
        $dataset = Dataset::where('pid', "=", $pid)->first();
        return $this->destroy($request, $dataset->id);
    }

    public function updateByPid(UpdateDataset $request, string $pid)
    {
        $dataset = Dataset::where('pid', "=", $pid)->first();
        return $this->update($request, $dataset->id);
    }

    /**
     * @OA\Get(
     *    path="/api/v1/datasets/export",
     *    operationId="export_datasets",
     *    tags={"Datasets"},
     *    summary="DatasetController@export",
     *    description="Export CSV Of All Datasets",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="dataset_id",
     *       in="query",
     *       description="dataset id",
     *       required=false,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="dataset id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="CSV file",
     *       @OA\MediaType(
     *          mediaType="text/csv",
     *          @OA\Schema(
     *             type="string",
     *             example="Title,""Publisher name"",Version,""Last Activity"",""Method of dataset creation"",Status,""Metadata detail""\n""Publications mentioning HDRUK"",""Health Data Research UK"",2.0.0,""2023-04-21T11:31:00.000Z"",MANUAL,ACTIVE,""{""properties\/accessibility\/usage\/dataUseRequirements"":{""id"":""95c37b03-54c4-468b-bda4-4f53f9aaaadd"",""namespace"":""hdruk.profile"",""key"":""properties\/accessibility\/usage\/dataUseRequirements"",""value"":""N\/A"",""lastUpdated"":""2023-12-14T11:31:11.312Z""},""properties\/required\/gatewayId"":{""id"":""8214d549-db98-453f-93e8-d88c6195ad93"",""namespace"":""hdruk.profile"",""key"":""properties\/required\/gatewayId"",""value"":""1234"",""lastUpdated"":""2023-12-14T11:31:11.311Z""}""",
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    )
     * )
     */
    public function export(Request $request): StreamedResponse
    {
        $teamId = $request->query('team_id', null);
        $datasetId = $request->query('dataset_id', null);
        $datasets = Dataset::when($teamId, function ($query) use ($teamId) {
            return $query->where('team_id', '=', $teamId);
        })->when($datasetId, function ($query) use ($datasetId) {
            return $query->where('id', '=', $datasetId);
        });

        $results = $datasets->select('datasets.*')->get();

        // collect all the required information
        foreach ($results as $dataset) {
            $dataset['metadata'] = $dataset->latestVersion();
        }

        // callback function that writes to php://output
        $response = new StreamedResponse(
            function () use ($results) {
                // Open output stream
                $handle = fopen('php://output', 'w');

                $headerRow = [
                    'Title',
                    'Publisher name',
                    'Last Activity',
                    'Method of dataset creation',
                    'Status',
                    'Metadata detail',
                ];

                // Add CSV headers
                fputcsv($handle, $headerRow);

                // add the given number of rows to the file.
                foreach ($results as $rowDetails) {
                    $metadata = $rowDetails['metadata']['metadata'];

                    $publisherName = $metadata['metadata']['summary']['publisher'];
                    if (version_compare(Config::get('metadata.GWDM.version'), "1.1", "<")) {
                        $publisherName = $publisherName['publisherName'];
                    } else {
                        $publisherName = $publisherName['name'];
                    }

                    $row = [
                        $metadata['metadata']['summary']['title'] !== null ?
                            $metadata['metadata']['summary']['title'] : '',
                        $publisherName !== null ? $publisherName : '',
                        $rowDetails['updated_at'] !== null ? $rowDetails['updated_at'] : '',
                        (string)strtoupper($rowDetails['create_origin']),
                        (string)strtoupper($rowDetails['status']),
                        $metadata['metadata'] !== null ? (string)json_encode($metadata['metadata']) : '',
                    ];
                    fputcsv($handle, $row);
                }

                // Close the output stream
                fclose($handle);
            }
        );

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename="Datasets.csv"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * @OA\Get(
     *    path="/api/v1/datasets/export/mock",
     *    operationId="export_mock_dataset",
     *    tags={"Datasets"},
     *    summary="DatasetController@exportMock",
     *    description="Export Mock",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="type",
     *       in="query",
     *       description="type export",
     *       required=true,
     *       @OA\Schema(
     *          type="string",
     *          description="type export",
     *          enum={"template_dataset_structural_metadata", "dataset_metadata"}
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="CSV file",
     *       @OA\MediaType(
     *          mediaType="text/csv",
     *          @OA\Schema(
     *             type="string",
     *             example="Title,""Publisher name"",Version,""Last Activity"",""Method of dataset creation"",Status,""Metadata detail""\n""Publications mentioning HDRUK"",""Health Data Research UK"",2.0.0,""2023-04-21T11:31:00.000Z"",MANUAL,ACTIVE,""{""properties\/accessibility\/usage\/dataUseRequirements"":{""id"":""95c37b03-54c4-468b-bda4-4f53f9aaaadd"",""namespace"":""hdruk.profile"",""key"":""properties\/accessibility\/usage\/dataUseRequirements"",""value"":""N\/A"",""lastUpdated"":""2023-12-14T11:31:11.312Z""},""properties\/required\/gatewayId"":{""id"":""8214d549-db98-453f-93e8-d88c6195ad93"",""namespace"":""hdruk.profile"",""key"":""properties\/required\/gatewayId"",""value"":""1234"",""lastUpdated"":""2023-12-14T11:31:11.311Z""}""",
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="File Not Found",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="file_not_found")
     *       ),
     *    ),
     * )
     */
    public function exportMock(Request $request)
    {
        try {
            $exportType = $request->query('type', null);
            $file = '';

            switch (strtolower($exportType)) {
                case 'template_dataset_structural_metadata':
                    $file = Config::get('mock_data.template_dataset_structural_metadata');
                    break;
                case 'dataset_metadata':
                    $file = Config::get('mock_data.mock_dataset_metadata');
                    break;
                default:
                    return response()->json(['error' => 'File not found.'], 404);
            }

            if (!Storage::disk('mock')->exists($file)) {
                return response()->json(['error' => 'File not found.'], 404);
            }

            return Storage::disk('mock')
                ->download($file)
                ->setStatusCode(Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function extractMetadata(Mixed $metadata)
    {

        // Pre-process check for incoming data from a resource that passes strings
        // when we expect an associative array. FMA passes strings, this
        // is a safe-guard to ensure execution is unaffected by other data types.
        if (isset($metadata['metadata'])) {
            if (is_string($metadata['metadata'])) {
                $tmpMetadata['metadata'] = json_decode($metadata['metadata'], true);
                unset($metadata['metadata']);
                $metadata = $tmpMetadata;
            }
        } elseif (is_string($metadata)) {
            $tmpMetadata['metadata'] = json_decode($metadata, true);
            unset($metadata);
            $metadata = $tmpMetadata;
        }
        return $metadata;
    }
}
