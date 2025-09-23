<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Jobs\TermExtraction;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use App\Jobs\LinkageExtraction;
use App\Http\Traits\CheckAccess;
use Illuminate\Http\JsonResponse;
use App\Models\Traits\ModelHelpers;
use App\Http\Controllers\Controller;
use App\Http\Traits\MetadataOnboard;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Traits\MetadataVersioning;
use Illuminate\Support\Facades\Storage;
use MetadataManagementController as MMC;
use App\Http\Requests\Dataset\GetDataset;
use App\Http\Requests\Dataset\EditDataset;
use App\Http\Requests\Dataset\TestDataset;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Requests\Dataset\CreateDataset;
use App\Http\Requests\Dataset\ExportDataset;
use App\Http\Requests\Dataset\UpdateDataset;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Exports\DatasetStructuralMetadataExport;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Arr;

class DatasetController extends Controller
{
    use MetadataVersioning;
    use GetValueByPossibleKeys;
    use MetadataOnboard;
    use CheckAccess;
    use ModelHelpers;

    /**
     * @OA\Get(
     *    path="/api/v1/datasets",
     *    deprecated=true,
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
            list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);

            $matches = [];
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
            )->select(['id'])->get();

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
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dataset get all',
            ]);

            return response()->json(
                $datasets
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
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
     *    deprecated=true,
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
     *    deprecated=true,
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

            list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);

            if (!$dataset) {
                return response()->json(['message' => 'Dataset not found'], 404);
            }

            // Get only the very latest metadata version ID, and process all related
            // objects on this, rather than excessively calling latestDataset()-><relation>.
            $latestVersionID = $dataset->latestVersionID($id);

            // This was incorrectly using the dataset ID, not the version ID. Thus leaving us
            // at the mercy of the sql optimiser and whatever order it decided to return at
            // time. Changed to use the version ID to ensure we get the correct 'latest' version.
            $datasetVersion = DatasetVersion::where('id', $latestVersionID)
                ->with(['tools', 'spatialCoverage', 'namedEntities', 'collections', 'durs', 'publications'])
                ->select('id')
                ->first();

            $dataset->tools = $datasetVersion->tools;
            $dataset->tools_count = $dataset->tools->count();
            $dataset->durs = $datasetVersion->durs;
            $dataset->durs_count = $dataset->durs->count();
            $dataset->collections = $datasetVersion->collections;
            $dataset->collections_count = $dataset->collections->count();
            $dataset->publications = $datasetVersion->publications;
            $dataset->publications_count = $dataset->publications->count();
            $dataset->spatialCoverage = $datasetVersion->spatialCoverage;
            $dataset->named_entities = $datasetVersion->namedEntities;

            unset($datasetVersion);

            $outputSchemaModel = $request->query('schema_model');
            $outputSchemaModelVersion = $request->query('schema_version');

            // Return the latest metadata for this dataset
            if (!($outputSchemaModel && $outputSchemaModelVersion)) {
                $withLinks = DatasetVersion::where('id', $latestVersionID)
                    ->with(['linkedDatasetVersions'])
                    ->first();
                if ($withLinks) {
                    $dataset->setAttribute('versions', [$withLinks]);
                }
            }

            if ($outputSchemaModel && $outputSchemaModelVersion) {
                $latestVersion = $dataset->latestVersion();

                $translated = MMC::translateDataModelType(
                    json_encode($latestVersion->metadata),
                    $outputSchemaModel,
                    $outputSchemaModelVersion,
                    Config::get('metadata.GWDM.name'),
                    Config::get('metadata.GWDM.version'),
                );

                if ($translated['wasTranslated']) {
                    $withLinks = DatasetVersion::where('id', $latestVersion['id'])
                        ->with(['reducedLinkedDatasetVersions'])
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
                $latestVersionId = $dataset->latestVersionID($id);
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
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dataset get ' . $id,
            ]);

            // linkages
            $dataset->setAttribute('linkages', $this->getLinkages($latestVersionID));

            return response()->json([
                'message' => 'success',
                'data' => $dataset,
            ], 200);

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function getLinkages($datasetVersionId)
    {
        $datasetLinkages = DatasetVersionHasDatasetVersion::where([
            'dataset_version_source_id' => $datasetVersionId,
        ])
        ->get()
        ->map(function ($linkage) {
            $dv = DatasetVersion::where([
                'id' => $linkage->dataset_version_target_id,
            ])->select(['id', 'dataset_id', 'short_title'])->first();

            if (is_null($dv)) {
                return null;
            }

            $d = Dataset::where([
                'id' => $dv->dataset_id,
            ])->select(['id', 'status'])->first();

            if (is_null($d)) {
                return null;
            }

            if ($d->status !== Dataset::STATUS_ACTIVE) {
                return null;
            }

            return [
                'title' => $dv->short_title,
                'url' => env('GATEWAY_URL') . '/en/dataset/' . $d->id,
                'dataset_id' => $d->id,
                'linkage_type' => $linkage->linkage_type,
            ];
        })
        ->filter()
        ->values()
        ->toArray();

        $datasetVersion = DatasetVersion::where('id', $datasetVersionId)->first();
        $metadataLinkage = $datasetVersion['metadata']['metadata']['linkage']['datasetLinkage'] ?? [];
        $allTitles = [];
        foreach ($metadataLinkage as $linkageType => $link) {
            if (($link) && is_array($link)) {
                foreach ($link as $l) {
                    $allTitles[] = [
                        'title' => $l['title'],
                        'linkage_type' => $linkageType,
                    ];
                }
            }
        }
        $gatewayTitles = array_column($datasetLinkages, 'title');

        foreach ($allTitles as $title) {
            if (($title['title']) && (!in_array($title['title'], $gatewayTitles))) {
                $datasetLinkages[] = [
                    'title' => $title['title'],
                    'url' => null,
                    'dataset_id' => null,
                    'linkage_type' => $title['linkage_type']
                ];
            }
        }

        return $datasetLinkages;
    }

    /**
     * @OA\Post(
     *    path="/api/v1/datasets",
     *    deprecated=true,
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
        list($userId, $teamId, $createOrigin, $status) = $this->getAccessorUserAndTeam($request);

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $this->checkAccess($input, $teamId, null, 'team', $request->header());

        // try {
        $elasticIndexing = $request->boolean('elastic_indexing', false);

        $team = Team::where('id', $teamId)->first()->toArray();

        // dd($input['metadata']);
        $input['metadata'] = $this->extractMetadata($input['metadata']);
        $input['status'] = $status;
        $input['user_id'] = $userId;
        $input['team_id'] = $teamId;
        $input['create_origin'] = $createOrigin;

        $inputSchema = $request->query('input_schema', null);
        $inputVersion = $request->query('input_version', null);

        // Ensure title is present for creating a dataset
        // dd($input['metadata']['metadata']);
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
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'team_id' => $teamId,
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
        // } catch (Exception $e) {
        //     Auditor::log([
        //         'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
        //         'team_id' => $teamId,
        //         'action_type' => 'EXCEPTION',
        //         'action_name' => class_basename($this) . '@' . __FUNCTION__,
        //         'description' => $e->getMessage(),
        //     ]);

        //     throw new Exception($e->getMessage());
        // }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/datasets/{id}",
     *    deprecated=true,
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
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team', $request->header());

        try {
            $elasticIndexing = $request->boolean('elastic_indexing', false);
            $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ? $input['is_cohort_discovery'] : false;

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

            $inputSchema = isset($input['metadata']['schemaModel']) ?
                $input['metadata']['schemaModel'] : $request->query('input_schema', null);
            $inputVersion = isset($input['metadata']['schemaVersion']) ?
                $input['metadata']['schemaVersion'] : $request->query('input_version', null);

            $submittedMetadata = ($input['metadata']['metadata'] ?? $input['metadata']);
            $gwdmMetadata = null;
            $useGWDMetada = false;
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
                $useGWDMetada = true;
            } else {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed.',
                    'details' => $traserResponse,
                ], 400);
            }

            // Update the existing dataset parent record with incoming data
            $updateTime = now();
            $currDataset->update([
                'user_id' => $userId,
                'team_id' => $teamId,
                'updated' => $updateTime,
                'pid' => $currentPid,
                'create_origin' => $createOrigin,
                'status' => $input['status'] ?? 'ACTIVE',
                'is_cohort_discovery' => $isCohortDiscovery,
            ]);

            $versionNumber = $currDataset->lastMetadataVersionNumber()->version;

            if (!is_array($submittedMetadata)) {
                $submittedMetadata = json_decode($submittedMetadata, true);
            }

            $datasetVersionId = $this->updateMetadataVersion(
                $currDataset,
                $gwdmMetadata,
                $submittedMetadata,
            );

            // Dispatch term extraction to a subprocess if the dataset moves from draft to active
            if ($request['status'] === Dataset::STATUS_ACTIVE) {

                LinkageExtraction::dispatch(
                    $currDataset->id,
                    $datasetVersionId,
                );
                if (Config::get('ted.enabled')) {
                    $tedMetadata = ($useGWDMetada) ? $gwdmMetadata : $input['metadata'];
                    $tedData = Config::get('ted.use_partial') ? $tedMetadata['summary'] : $tedMetadata;

                    TermExtraction::dispatch(
                        $currDataset->id,
                        $datasetVersionId,
                        $versionNumber,
                        base64_encode(gzcompress(gzencode(json_encode($tedData), 6))),
                        $elasticIndexing,
                        Config::get('ted.use_partial')
                    );
                }
            }

            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' with version ' . ($versionNumber) . ' updated',
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
                'user_id' => $userId,
                'team_id' => $teamId,
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
     *    deprecated=true,
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
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team', $request->header());

        try {
            if ($request->has('unarchive')) {
                $datasetModel = Dataset::withTrashed()->where(['id' => $id]) ->first();

                if (in_array($request['status'], [Dataset::STATUS_ACTIVE, Dataset::STATUS_DRAFT])) {
                    $datasetModel->status = $request['status'];
                    $datasetModel->deleted_at = null;
                    $datasetModel->save();

                    $metadata = DatasetVersion::withTrashed()->where('dataset_id', $id)->latest()->first();
                    $metadata->deleted_at = null;
                    $metadata->save();

                    if ($request['status'] === Dataset::STATUS_ACTIVE) {
                        LinkageExtraction::dispatch(
                            $datasetModel->id,
                            $metadata->id,
                        );
                    }

                    Auditor::log([
                        'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                        'team_id' => $teamId,
                        'action_type' => 'UPDATE',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => 'Dataset ' . $id . ' marked as ' . strtoupper($request['status']) . ' updated',
                    ]);

                } else {
                    $message = 'unknown status type';

                    Auditor::log([
                        'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                        'team_id' => $teamId,
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

                    $metadata = DatasetVersion::withTrashed()->where('dataset_id', $id)->latest()->first();

                    if ($request['status'] === Dataset::STATUS_ACTIVE) {
                        LinkageExtraction::dispatch(
                            $datasetModel->id,
                            $metadata->id,
                        );
                    }


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
                    'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                    'team_id' => $teamId,
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
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'team_id' => $teamId,
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
     *      deprecated=true,
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
    public function destroy(Request $request, string $id) // softdelete
    {
        $input = $request->all();
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team', $request->header());

        try {
            $dataset = Dataset::where('id', $id)->first();

            MMC::deleteDataset($id, true);

            Auditor::log([
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'team_id' => $teamId,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/datasets/test",
     *    operationId="datasets_test",
     *    tags={"datasets test"},
     *    summary="DatasetController@datasetTest",
     *    description="Datasets test",
     *    security={{"bearerAppAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass datasets payload",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
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
    public function datasetTest(TestDataset $request)
    {
        try {
            $input = $request->all();

            //send the payload to traser
            // - traser will return the input unchanged if the data is
            //   already in the GWDM with GWDM_CURRENT_VERSION
            // - if it is not, traser will try to work out what the metadata is
            //   and translate it into the GWDM
            // - otherwise traser will return a non-200 error
            $traserResponse = MMC::translateDataModelType(
                json_encode($input['metadata']),
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version')
            );

            if ($traserResponse['wasTranslated']) {
                return response()->json([
                    'message' => 'success',
                    'payload_received' => $input,
                ], 200);
            }

            return response()->json([
                'message' => 'metadata is in an unknown format and cannot be processed',
                'details' => $traserResponse,
                'payload_received' => $input,
            ], 400);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function destroyByPid(Request $request, string $pid) // softdelete
    {
        $input = $request->all();
        $teamId = (int)$input['team_id'];
        $this->checkAccess($input, $teamId, null, 'team');
        $dataset = Dataset::where('pid', "=", $pid)->first();
        return $this->destroy($request, $dataset->id);
    }

    public function federationDestroyByPid(Request $request, string $pid) // softdelete
    {
        $input = $request->all();
        $teamId = (int)$input['team_id'];
        $this->checkAccess($input, $teamId, null, 'team');

        $dataset = Dataset::where('pid', '=', $pid)
                  ->where('team_id', '=', $teamId)
                  ->first();

        if ($dataset->team_id !== $teamId) {
            return response()->json(['error' => 'Forbidden', 'message' => 'Cannot delete dataset, it belongs to '.$dataset->team_id.', not team ' . $teamId], 403);
        }

        return $this->destroy($request, $dataset->id);
    }

    public function federationUpdateByPid(UpdateDataset $request, string $pid)
    {
        $input = $request->all();
        $teamId = (int) $input['team_id'];
        $dataset = Dataset::where('pid', '=', $pid)
                  ->where('team_id', '=', $teamId)
                  ->first();

        if ($dataset->team_id !== $teamId) {
            return response()->json(['error' => 'Forbidden', 'message' => 'Cannot update dataset, it belongs to '.$dataset->team_id.', not team ' . $teamId], 403);
        }

        $this->checkAccess($input, $teamId, null, 'team');
        return $this->update($request, $dataset->id);
    }

    public function updateByPid(UpdateDataset $request, string $pid)
    {
        $input = $request->all();
        $teamId = (int)$input['team_id'];
        $this->checkAccess($input, $teamId, null, 'team');
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
     *    path="/api/v1/datasets/export_metadata/{id}",
     *    operationId="export_dataset_metadata",
     *    tags={"Datasets"},
     *    summary="DatasetController@exportMetadata",
     *    description="Export Structural Metadata CSV of a single dataset",
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
     *       name="download_type",
     *       in="query",
     *       description="download type",
     *       required=true,
     *       example="structural",
     *       @OA\Schema(
     *          type="string",
     *          description="download type",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="CSV file",
     *       @OA\MediaType(
     *          mediaType="text/csv",
     *          @OA\Schema(
     *             type="string",
     *             example="",
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Bad request",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Invalid argument(s)")
     *       ),
     *    )
     * )
     */
    public function exportMetadata(ExportDataset $request, int $id): StreamedResponse
    {
        $input = $request->all();
        $download_type = strtolower($input['download_type']);

        $dataset = Dataset::where('id', '=', $id)->first();

        $result = $dataset->latestVersion()['metadata']['metadata'];

        $response = new StreamedResponse(
            function () use ($result, $download_type) {
                // Open output stream
                $handle = fopen('php://output', 'w');

                if ($download_type === 'structural') {
                    $headerRow = [
                        'Table Name',
                        'Table Description',
                        'Column Name',
                        'Column Description',
                        'Data Type',
                        'Sensitive',
                    ];

                    // Add CSV headers
                    fputcsv($handle, $headerRow);
                    // add the given number of rows to the file.
                    foreach ($result['structuralMetadata'] as $rowDetails) {
                        $row = [
                            $rowDetails['name'] !== null ? $rowDetails['name'] : '',
                            $rowDetails['description'] !== null ? $rowDetails['description'] : '',
                            $rowDetails['columns'][0]['name'] !== null ? $rowDetails['columns'][0]['name'] : '',
                            $rowDetails['columns'][0]['description'] !== null ? str_replace('\n', '', $rowDetails['columns'][0]['description']) : '',
                            $rowDetails['columns'][0]['dataType'] !== null ? $rowDetails['columns'][0]['dataType'] : '',
                            $rowDetails['columns'][0]['sensitive'] !== null ? ($rowDetails['columns'][0]['sensitive'] === true ? 'true' : 'false') : '',
                        ];
                        fputcsv($handle, $row);
                    }
                } elseif ($download_type === 'observations') {
                    $headerRow = [
                        'Observed Node',
                        'Disambiguating Description',
                        'Measured Value',
                        'Measured Property',
                        'Observation Date',
                    ];

                    // Add CSV headers
                    fputcsv($handle, $headerRow);
                    // add the given number of rows to the file.
                    foreach ($result['observations'] as $rowDetails) {
                        $row = [
                            $rowDetails['observedNode'] !== null ? $rowDetails['observedNode'] : '',
                            $rowDetails['disambiguatingDescription'] !== null ? $rowDetails['disambiguatingDescription'] : '',
                            $rowDetails['measuredValue'] !== null ? $rowDetails['measuredValue'] : '',
                            $rowDetails['measuredProperty'] !== null ? $rowDetails['measuredProperty'] : '',
                            $rowDetails['observationDate'] !== null ? $rowDetails['observationDate'] : '',
                        ];
                        fputcsv($handle, $row);
                    }
                } elseif ($download_type === 'metadata') {
                    $headerRow = [
                        'Section',
                        'Value',
                        'Field',
                    ];

                    // Add CSV headers
                    fputcsv($handle, $headerRow);

                    // Note that the contents here need to be manually kept up to date with the contents of
                    // gateway-web-2/src/app/[locale]/(logged-out)/dataset/[datasetId]/config.tsx.
                    // The 'Summary Demographics' rows match the summary boxes at the top of the dataset landing page
                    $rows = [
                        ['Dataset', 'Name', $this->getValueFromPath($result, 'summary/title')],
                        ['Dataset', 'Gateway URL', $this->getValueFromPath($result, 'required/revisions/0/url')],
                        ['Dataset', 'Dataset Type', $this->getValueFromPath($result, 'summary/datasetType')],
                        ['Dataset', 'Dataset Sub-type', $this->getValueFromPath($result, 'summary/datasetSubType')],
                        ['Dataset', 'Collection Sources', $this->getValueFromPath($result, 'provenance/origin/collectionSituation')],

                        ['Summary Demographics', 'Population Size', $this->getValueFromPath($result, 'summary/populationSize') === "-1" ? "" : $this->getValueFromPath($result, 'summary/populationSize')],
                        ['Summary Demographics', 'Years', $this->getValueFromPath($result, 'provenance/temporal/startDate')],
                        ['Summary Demographics', 'Associate BioSamples', $this->getValueFromPath($result, 'coverage/materialType')],
                        ['Summary Demographics', 'Geographic coverage', $this->getValueFromPath($result, 'coverage/spatial')],
                        ['Summary Demographics', 'Lead time', $this->getValueFromPath($result, 'accessibility/access/deliveryLeadTime')],

                        ['Summary', 'Abstract', $this->getValueFromPath($result, 'summary/abstract')],
                        ['Summary', 'DOI for dataset', $this->getValueFromPath($result, 'summary/doiName')],

                        ['Documentation', 'Description', $this->getValueFromPath($result, 'documentation/description')],
                        ['Documentation', 'Dataset type', $this->getValueFromPath($result, 'provenance/origin/datasetType')],
                        ['Documentation', 'Dataset sub-type', $this->getValueFromPath($result, 'provenance/origin/datasetSubType')],
                        ['Documentation', 'Dataset population size', $this->getValueFromPath($result, 'summary/populationSize')],
                        ['Documentation', 'Associated media', $this->getValueFromPath($result, 'documentation/associatedMedia')],
                        ['Documentation', 'Synthetic data web link', $this->getValueFromPath($result, 'structuralMetadata/syntheticDataWebLink')],

                        ['Keywords', 'Keywords', $this->getValueFromPath($result, 'summary/keywords')],

                        ['Provenance', 'Purpose of dataset collection', $this->getValueFromPath($result, 'provenance/origin/purpose')],
                        ['Provenance', 'Source of dataset extraction', $this->getValueFromPath($result, 'provenance/origin/source')],
                        ['Provenance', 'Collection source setting', $this->getValueFromPath($result, 'provenance/origin/collectionSource')],
                        ['Provenance', 'Patient pathway description', $this->getValueFromPath($result, 'coverage/pathway')],
                        ['Provenance', 'Image contrast', $this->getValueFromPath($result, 'provenance/origin/imageContrast')],
                        ['Provenance', 'Biological sample availability', $this->getValueFromPath($result, 'coverage/materialType')],

                        ['Details', 'Publishing frequency', $this->getValueFromPath($result, 'provenance/temporal/publishingFrequency')],
                        ['Details', 'Version', $this->getValueFromPath($result, 'version')],
                        ['Details', 'Modified', $this->getValueFromPath($result, 'modified')],
                        ['Details', 'Distribution release date', $this->getValueFromPath($result, 'provenance/termporal/distributionReleaseDate')],
                        ['Details', 'Citation Requirements', implode(',', $this->getValueFromPath($result, 'accessibility/usage/resourceCreator'))],

                        ['Coverage', 'Start date', $this->getValueFromPath($result, 'provenance/temporal/startDate')],
                        ['Coverage', 'End date', $this->getValueFromPath($result, 'provenance/temporal/endDate')],
                        ['Coverage', 'Time lag', $this->getValueFromPath($result, 'provenance/temporal/timeLag')],
                        ['Coverage', 'Geographic coverage', $this->getValueFromPath($result, 'coverage/spatial')],
                        ['Coverage', 'Minimum age range', $this->getValueFromPath($result, 'coverage/typicalAgeRangeMin')],
                        ['Coverage', 'Maximum age range', $this->getValueFromPath($result, 'coverage/typicalAgeRangeMax')],
                        ['Coverage', 'Follow-up', $this->getValueFromPath($result, 'coverage/followUp')],
                        ['Coverage', 'Dataset completeness', $this->getValueFromPath($result, 'coverage/datasetCompleteness')],

                        ['Omics', 'Assay', $this->getValueFromPath($result, 'omics/assay')],
                        ['Omics', 'Platform', $this->getValueFromPath($result, 'omics/platform')],

                        ['Accessibility', 'Language', $this->getValueFromPath($result, 'accessibility/formatAndStandards/language')],
                        ['Accessibility', 'Alignment with standardised data models', $this->getValueFromPath($result, 'accessibility/formatAndStandards/conformsTo')],
                        ['Accessibility', 'Controlled vocabulary', $this->getValueFromPath($result, 'accessibility/formatAndStandards/vocabularyEncodingScheme')],
                        ['Accessibility', 'Format', $this->getValueFromPath($result, 'accessibility/formatAndStandards/format')],

                        ['Data Access Request', 'Dataset pipeline status', $this->getValueFromPath($result, 'documentation/inPipeline')],
                        ['Data Access Request', 'Access rights', $this->getValueFromPath($result, 'accessibility/access/accessRights')],
                        ['Data Access Request', 'Time to dataset access', $this->getValueFromPath($result, 'accessibility/access/deliveryLeadTime')],
                        ['Data Access Request', 'Access request cost', $this->getValueFromPath($result, 'accessibility/access/accessRequestCost')],
                        ['Data Access Request', 'Access method category', $this->getValueFromPath($result, 'accessibility/access/accessServiceCategory')],
                        ['Data Access Request', 'Access mode', $this->getValueFromPath($result, 'accessibility/access/accessMode')],
                        ['Data Access Request', 'Access service description', $this->getValueFromPath($result, 'accessibility/access/accessService')],
                        ['Data Access Request', 'Jurisdiction', $this->getValueFromPath($result, 'accessibility/access/jurisdiction')],
                        ['Data Access Request', 'Data use limitation', $this->getValueFromPath($result, 'accessibility/usage/dataUseLimitation')],
                        ['Data Access Request', 'Data use requirements', $this->getValueFromPath($result, 'accessibility/usage/dataUseRequirements')],
                        ['Data Access Request', 'Data Controller', $this->getValueFromPath($result, 'accessibility/access/dataController')],
                        ['Data Access Request', 'Data Processor', $this->getValueFromPath($result, 'accessibility/access/dataProcessor')],
                        ['Data Access Request', 'Investigations', $this->getValueFromPath($result, 'enrichmentAndLinkage/investigations')],

                        ['Demographics', 'Demographic Frequency', $this->getValueFromPath($result, 'demographicFrequency')],
                    ];

                    foreach ($rows as $row) {
                        fputcsv($handle, $row);
                    }
                }

                // Close the output stream
                fclose($handle);
            }
        );

        $response->headers->set('Content-Type', 'text/csv');
        $filename = $id . '_' . $result['summary']['title'];
        if ($download_type === 'structural') {
            $filename .= '_Structural_Metadata.csv';
        } elseif ($download_type === 'observations') {
            $filename .= '_Observations.csv';
        } elseif ($download_type === 'metadata') {
            $filename .= '_Metadata.csv';
        } else {
            $filename .= '.csv';
        }
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
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

    /**
     * Extracts metadata from the given mixed input.
     *
     * @param Mixed $metadata
     * @return array
     */
    private function extractMetadata(Mixed $metadata)
    {
        if (is_array($metadata) && Arr::has($metadata, 'metadata.metadata')) {
            $metadata = $metadata['metadata'];
        } elseif (is_array($metadata) && !Arr::has($metadata, 'metadata')) {
            $metadata = [
                'metadata' => $metadata,
            ];
        }

        if (is_string($metadata) && isJsonString($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        // Pre-process check for incoming data from a resource that passes strings
        // when we expect an associative array. FMA passes strings, this
        // is a safe-guard to ensure execution is unaffected by other data types.


        if (isset($metadata['metadata']) && is_string($metadata['metadata']) && isJsonString($metadata['metadata'])) {
            $tmpMetadata['metadata'] = json_decode($metadata['metadata'], true);
            unset($metadata['metadata']);
            $metadata = $tmpMetadata;
        }

        return $metadata;
    }

    public function getValueFromPath(array $item, string $path)
    {
        $keys = explode('/', $path);

        $return = $item;
        foreach ($keys as $key) {
            if (isset($return[$key])) {
                $return = $return[$key];
            } else {
                return null;
            }
        }

        return $return;
    }
}
