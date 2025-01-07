<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;

use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Jobs\TermExtraction;
use App\Jobs\LinkageExtraction;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\IndexElastic;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Http\Traits\MetadataOnboard;
use App\Http\Traits\MetadataVersioning;
use App\Models\Traits\ModelHelpers;

use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\Storage;
use MetadataManagementController as MMC;
use App\Http\Requests\V2\Dataset\GetDataset;
use App\Http\Requests\V2\Dataset\EditDataset;
use App\Http\Traits\RequestTransformation;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Requests\V2\Dataset\CreateDataset;
use App\Http\Requests\V2\Dataset\DeleteDataset;
use App\Http\Requests\V2\Dataset\ExportDataset;
use App\Http\Requests\V2\Dataset\UpdateDataset;
use App\Exports\DatasetStructuralMetadataExport;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatasetController extends Controller
{
    use MetadataVersioning;
    use IndexElastic;
    use GetValueByPossibleKeys;
    use MetadataOnboard;
    use CheckAccess;
    use ModelHelpers;
    use RequestTransformation;

    /**
     * @OA\Get(
     *    path="/api/v2/datasets",
     *    operationId="fetch_all_datasets_v2",
     *    tags={"Datasets"},
     *    summary="DatasetController@index",
     *    description="Returns a list of all datasets",
     *    security={{"bearerAuth":{}}},
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
     *    @OA\Parameter(
     *       name="with_metadata",
     *       in="query",
     *       description="Boolean whether to return dataset metadata",
     *       example="true",
     *       @OA\Schema(
     *          type="string",
     *          description="Boolean whether to return dataset metadata",
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
            $filterStatus = $request->query('status', null);
            $withMetadata = $request->boolean('with_metadata', true);

            $sort = $request->query('sort', 'created:desc');

            $tmp = explode(":", $sort);
            $sortField = $tmp[0];
            $sortDirection = $tmp[1] ?? 'asc';

            // apply any initial filters to get initial datasets
            $initialDatasets = Dataset::when($filterStatus, function ($query) use ($filterStatus) {
                return $query->where('status', '=', $filterStatus);
            })->select(['id'])->get();

            // Map initially found datasets to just ids.
            foreach ($initialDatasets as $ds) {
                $matches[] = $ds->id;
            }

            $filterTitle = $request->query('title', null);

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
                    ->first();

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
            $datasets = Dataset::whereIn('id', $matches)
                ->when($withMetadata, fn ($query) => $query->with('latestMetadata'))
                ->applySorting()
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
     *    path="/api/v2/datasets/count/{field}",
     *    operationId="count_unique_fields_v2",
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
            })->select($field)
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
     *    path="/api/v2/datasets/{id}",
     *    operationId="fetch_datasets_v2",
     *    tags={"Datasets"},
     *    summary="DatasetController@show",
     *    description="Get publicly visible dataset by id",
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
            $dataset = Dataset::with("team")->where("status", DATASET::STATUS_ACTIVE)->find($id);

            if (!$dataset) {
                return response()->json(['message' => 'Dataset not found'], 404);
            }

            // Get only the very latest metadata version ID, and process all related
            // objects on this, rather than excessively calling latestDataset()-><relation>.
            $latestVersionID = $dataset->latestVersionID($id);

            // Inject attributes via the dataset version table
            // notes Calum 12th August 2024...
            // - This is a mess.. why is `publications_count` returning something different than dataset->allPublications??
            // - Tools linkage not returned
            // - For the FE I just need a tools linkage count so i'm gonna return `count(dataset->allTools)` for now
            // - Same for collections
            // - Leaving this as it is as im not 100% sure what any FE knock-on effect would be
            //
            // LS - Have replaced publications and dur counts with a raw count of linked relations via
            // the *_has_* lookups.
            $dataset->setAttribute('durs_count', $this->countDursForDatasetVersion($latestVersionID));
            $dataset->setAttribute('publications_count', $this->countPublicationsForDatasetVersion($latestVersionID));
            // This needs looking into, as helpful as attributes are, they're actually
            // really poor in terms of performance. It'd be quicker to directly mutate
            // a model in memory. That is, however, lazy, and better still would be
            // to translate these to raw sql, as I have done above.
            $dataset->setAttribute('tools_count', count($dataset->allTools));
            $dataset->setAttribute('collections_count', count($dataset->allCollections));
            $dataset->setAttribute('spatialCoverage', $dataset->allSpatialCoverages  ?? []);
            $dataset->setAttribute('durs', $dataset->allDurs  ?? []);
            $dataset->setAttribute('publications', $dataset->allPublications  ?? []);
            $dataset->setAttribute('named_entities', $dataset->allNamedEntities  ?? []);
            $dataset->setAttribute('collections', $dataset->allCollections  ?? []);

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
     *    path="/api/v2/datasets",
     *    operationId="create_datasets_v2",
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
        $teamId = (int)$input['team_id'];
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $this->checkAccess($input, $teamId, null, 'team');

        try {
            $elasticIndexing = $request->boolean('elastic_indexing', true);

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
     *    path="/api/v2/datasets/{id}",
     *    operationId="update_datasets_v2",
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
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team');

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

            $inputSchema = $input['metadata']['schemaModel'] ?? null;
            $inputVersion = $input['metadata']['schemaVersion'] ?? null;

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

            $datasetVersionId = $this->updateMetadataVersion(
                $currDataset,
                $gwdmMetadata,
                $submittedMetadata,
            );

            // Dispatch term extraction to a subprocess if the dataset moves from draft to active
            if($request['status'] === Dataset::STATUS_ACTIVE) {

                LinkageExtraction::dispatch(
                    $currDataset->id,
                    $datasetVersionId,
                );
                if(Config::get('ted.enabled')) {
                    $tedData = Config::get('ted.use_partial') ? $input['metadata']['metadata']['summary'] : $input['metadata']['metadata'];

                    TermExtraction::dispatch(
                        $currDataset->id,
                        $datasetVersionId,
                        $versionNumber,
                        base64_encode(gzcompress(gzencode(json_encode($tedData), 6))),
                        $elasticIndexing,
                        Config::get('ted.use_partial')
                    );
                } else {
                    $this->reindexElastic($currDataset->id);
                }
            } elseif($initDataset->status === Dataset::STATUS_ACTIVE) {
                $this->deleteDatasetFromElastic($currDataset->id);
            }

            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' with version ' . ($versionNumber) . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e);
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v2/datasets/{id}",
     *    operationId="patch_datasets_v2",
     *    tags={"Datasets"},
     *    summary="DatasetController@edit",
     *    description="Patch dataset by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error"),
     *       )
     *    )
     * )
     */
    public function edit(EditDataset $request, int $id)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team');

        try {
            //TODO: how to edit correctly, particularly the metadata? Assume if it's provided then it overwrites, otherwise leave as it is?
            $datasetModel = Dataset::where('id', $id)->first();

            $datasetModel->status = $request['status'];
            $datasetModel->save();

            $metadata = DatasetVersion::where('dataset_id', $id)->latest()->first();

            if($request['status'] === Dataset::STATUS_ACTIVE) {
                LinkageExtraction::dispatch(
                    $datasetModel->id,
                    $metadata->id,
                );
                $this->reindexElastic($id);
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
     *      path="/api/v2/datasets/{id}",
     *      operationId="delete_datasets_v2",
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
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team');

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
     *    path="/api/v2/datasets/export",
     *    operationId="export_datasets_v2",
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
                    if(version_compare(Config::get('metadata.GWDM.version'), "1.1", "<")) {
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
     *    path="/api/v2/datasets/export_metadata/{id}",
     *    operationId="export_dataset_metadata_v2",
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
                        'Section',
                        'Column name',
                        'Data type',
                        'Column description',
                        'Sensitive',
                    ];

                    // Add CSV headers
                    fputcsv($handle, $headerRow);
                    // add the given number of rows to the file.
                    foreach ($result['structuralMetadata'] as $rowDetails) { //TODO: check whether this is slow, or the `latestVersion` above is the limiter. Use the BHF dataset as the testcase
                        $row = [
                            $rowDetails['name'] !== null ? $rowDetails['name'] : '',
                            $rowDetails['columns'][0]['name'] !== null ? $rowDetails['columns'][0]['name'] : '',
                            $rowDetails['columns'][0]['dataType'] !== null ? $rowDetails['columns'][0]['dataType'] : '',
                            $rowDetails['columns'][0]['description'] !== null ? str_replace('\n', '', $rowDetails['columns'][0]['description']) : '',
                            $rowDetails['columns'][0]['sensitive'] !== null ? $rowDetails['columns'][0]['sensitive'] === true ? 'true' : 'false' : '',
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
     *    path="/api/v2/datasets/export/mock",
     *    operationId="export_mock_dataset_v2",
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
