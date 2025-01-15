<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;

use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasSpatialCoverage;
use App\Models\SpatialCoverage;

use App\Jobs\TermExtraction;
use App\Jobs\LinkageExtraction;
use MetadataManagementController as MMC;

use App\Http\Traits\IntegrationOverride;
use App\Http\Traits\MetadataOnboard;

use App\Http\Controllers\Controller;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Http\Requests\Dataset\GetDataset;
use App\Http\Requests\Dataset\TestDataset;
use App\Http\Requests\Dataset\CreateDataset;
use App\Http\Requests\Dataset\UpdateDataset;
use App\Http\Requests\Dataset\EditDataset;

class IntegrationDatasetController extends Controller
{
    use IntegrationOverride;
    use MetadataOnboard;

    /**
     * @OA\Get(
     *    path="/api/v1/integrations/datasets",
     *    operationId="fetch_all_datasets_integrations",
     *    tags={"Datasets"},
     *    summary="IntegrationDatasetController@index",
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
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $matches = [];
            $teamId = $request->query('team_id', null);
            $filterStatus = $request->query('status', null);
            $datasetId = $request->query('dataset_id', null);
            $mongoPId = $request->query('mongo_pid', null);

            // Injection to override the team_id in the scenario that an integration
            // is making the call, to only provide data the integration is allowed
            // to see
            $this->overrideTeamId($teamId, $request->headers->all());

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
                    'message' => 'Sort direction must be either: ' .
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
                    ->latest('version')->select('dataset_id')->first();

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
            $datasets = Dataset::with(['versions' => fn ($version) => $version->withTrashed()->latest()])
                ->whereIn('id', $matches)
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
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dataset get all',
            ]);

            return response()->json(
                $datasets
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/integrations/datasets/{id}",
     *    operationId="fetch_datasets_integrations",
     *    tags={"Datasets"},
     *    summary="IntegrationDatasetController@show",
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
    public function show(GetDataset $request, int $id): JsonResponse
    {

        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());
            $dataset = Dataset::findOrFail($id);

            // inject dataset Version Atributes
            $dataset->setAttribute('publications', $dataset->allPublications  ?? []);
            $dataset->setAttribute('named_entities', $dataset->allNamedEntities  ?? []);
            $dataset->setAttribute('collections', $dataset->allCollections  ?? []);

            if (!$dataset) {
                return response()->json([
                    'message' => 'Dataset not found'
                ], 404);
            }

            // Retrieve the latest version
            $latestVersion = $dataset->versions()->latest('version')->first();

            $this->checkAppCanHandleDataset($dataset->team_id, $request);

            $outputSchemaModel = $request->query('schema_model');
            $outputSchemaModelVersion = $request->query('schema_version');


            if ($outputSchemaModel && $outputSchemaModelVersion) {
                $version = $dataset->latestVersion();

                $translated = MMC::translateDataModelType(
                    json_encode($version->metadata),
                    $outputSchemaModel,
                    $outputSchemaModelVersion,
                    Config::get('metadata.GWDM.name'),
                    Config::get('metadata.GWDM.version'),
                );

                if ($translated['wasTranslated']) {
                    return response()->json([
                        'message' => 'success, translated to model=' . $outputSchemaModel .
                            " version=" . $outputSchemaModelVersion,
                        'data' => $translated['metadata'],
                    ], 200);
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

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
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
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/integrations/datasets",
     *    operationId="create_datasets_integrations",
     *    tags={"Datasets"},
     *    summary="IntegrationDatasetController@store",
     *    description="Create a new dataset",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
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
    public function store(CreateDataset $request): JsonResponse
    {
        try {
            $input = $request->all();

            // If this is coming from an integration, we override the default settings
            // so these aren't required as part of the payload and inferred from the
            // application token being used instead
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $team = Team::where('id', $applicationOverrideDefaultValues['team_id'])->first()->toArray();
            $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ?
                $input['is_cohort_discovery'] : false;

            $input['metadata'] = $this->extractMetadata($input);

            //send the payload to traser
            // - traser will return the input unchanged if the data is
            //   already in the GWDM with GWDM_CURRENT_VERSION
            // - if it is not, traser will try to work out what the metadata is
            //   and translate it into the GWDM
            // - otherwise traser will return a non-200 error

            $payload = $input['metadata'];
            $payload['extra'] = [
                'id' => 'placeholder',
                'pid' => 'placeholder',
                'datasetType' => 'Health and disease',
                'publisherId' => $team['pid'],
                'publisherName' => $team['name']
            ];

            $traserResponse = MMC::translateDataModelType(
                json_encode($payload),
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version'),
            );

            if ($traserResponse['wasTranslated']) {
                $input['metadata']['original_metadata'] = $input['metadata']['metadata'];
                $input['metadata']['metadata'] = $traserResponse['metadata'];

                $mongo_object_id = array_key_exists('mongo_object_id', $input) ?
                    $input['mongo_object_id'] : null;
                $mongo_id = array_key_exists('mongo_id', $input) ? $input['mongo_id'] : null;
                $mongo_pid = array_key_exists('mongo_pid', $input) ? $input['mongo_pid'] : null;
                $datasetid = array_key_exists('datasetid', $input) ? $input['datasetid'] : null;

                $pid = array_key_exists('pid', $input) ? $input['pid'] : (string) Str::uuid();

                $dataset = MMC::createDataset([
                    'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                        $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                    'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                        $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                    'mongo_object_id' => $mongo_object_id,
                    'mongo_id' => $mongo_id,
                    'mongo_pid' => $mongo_pid,
                    'datasetid' => $datasetid,
                    'created' => now(),
                    'updated' => now(),
                    'submitted' => now(),
                    'pid' => $pid,
                    'create_origin' => (isset($applicationOverrideDefaultValues['create_origin']) ?
                        $applicationOverrideDefaultValues['create_origin'] : $input['create_origin']),
                    'status' => (isset($applicationOverrideDefaultValues['status']) ?
                        $applicationOverrideDefaultValues['status'] : $input['status']),
                    'is_cohort_discovery' => $isCohortDiscovery,
                ]);


                $publisher = null;

                $revisions = [
                    [
                        "url" => env('GATEWAY_URL') . '/dataset' .'/' . $dataset->id . '?version=1.0.0',
                        'version' => $this->formatVersion(1)
                    ]
                ];

                $required = [
                        'gatewayId' => strval($dataset->id), //note: do we really want this in the GWDM?
                        'gatewayPid' => $dataset->pid,
                        'issued' => $dataset->created,
                        'modified' => $dataset->updated,
                        'revisions' => $revisions
                    ];

                // -------------------------------------------------------------------
                // * Create a new 'required' section for the metadata to be saved
                //    - otherwise this section is filled with placeholders by all translations to GWDM
                // * Force correct publisher field based on the team associated with
                //
                // Note:
                //     - This is hopefully a rare scenario when the BE has to be changed due to an update
                //        to the GWDM
                //     - future releases of the GWDM will hopefully not modify anything that we need to
                //       set via the MMC
                //     - we can't pass the publisherId nor the gatewayPid of the dataset to traser before
                //       they have been created, this is why we are doing this..
                //     - GWDM >= 1.1 versions have a change related to these sections of the GWDM
                //         - addition of the field 'version' in the required field
                //         - restructure of the 'publisher' in the summary field
                //            - publisher.publisherId --> publisher.gatewayId
                //            - publisher.publisherName --> publisher.name
                // -------------------------------------------------------------------
                if(version_compare(Config::get('metadata.GWDM.version'), '1.1', '<')) {
                    $publisher = [
                        'publisherId' => $team['pid'],
                        'publisherName' => $team['name'],
                    ];
                } else {
                    $version = $this->getVersion(1);
                    if(array_key_exists('version', $input['metadata']['metadata']['required'])) {
                        $version = $input['metadata']['metadata']['required']['version'];
                    }
                    $required['version'] = $version;
                    $publisher = [
                        'gatewayId' => $team['pid'],
                        'name' => $team['name'],
                    ];
                }

                $input['metadata']['metadata']['required'] = $required;
                $input['metadata']['metadata']['summary']['publisher'] = $publisher;

                //include a note of what the metadata was (i.e. which GWDM version)
                $input['metadata']['gwdmVersion'] =  Config::get('metadata.GWDM.version');

                $version = MMC::createDatasetVersion([
                    'dataset_id' => $dataset->id,
                    'metadata' => json_encode($input['metadata']),
                    'version' => 1,
                ]);

                // map coverage/spatial field to controlled list for filtering
                $this->mapCoverage($input['metadata'], $version);

                // Dispatch term extraction to a subprocess as it may take some time
                if($request['status'] === Dataset::STATUS_ACTIVE) {

                    LinkageExtraction::dispatch(
                        $dataset->id,
                        $version->id,
                    );

                    $tedData = Config::get('ted.use_partial') ? $input['metadata']['metadata']['summary'] : $input['metadata']['metadata'];

                    TermExtraction::dispatch(
                        $dataset->id,
                        $version->id,
                        '1',
                        base64_encode(gzcompress(gzencode(json_encode($tedData)))),
                        true,
                        Config::get('ted.use_partial')
                    );
                }

                Auditor::log([
                    'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                        $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                    'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                        $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => 'Dataset ' . $dataset->id . ' with version ' . $version->id . ' created',
                ]);

                return response()->json([
                    'message' => 'created',
                    'data' => $dataset->id,
                    'version' => $version->id,
                ], 201);
            } else {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed',
                    'details' => $traserResponse,
                ], 400);
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/integrations/datasets/{id}",
     *    operationId="update_datasets_integrations",
     *    tags={"Datasets"},
     *    summary="IntegrationDatasetController@update",
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

        try {
            $currDataset = Dataset::findOrFail($id);
            $this->checkAppCanHandleDataset($currDataset->team_id, $request);

            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());
            $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ?
                $input['is_cohort_discovery'] : false;

            $teamId = $applicationOverrideDefaultValues['team_id'];
            $userId = $applicationOverrideDefaultValues['user_id'];

            if (isset($request->header)) {
                $this->overrideUserId($userId, $request->header->all());
                $this->overrideTeamId($teamId, $request->header->all());
            }

            $user = User::where('id', $userId)->first();
            $team = Team::where('id', $teamId)->first();
            $currentPid = $currDataset->pid;

            $input['metadata'] = $this->extractMetadata($input);

            $payload = $input['metadata'];
            $payload['extra'] = [
                'id' => $id,
                'pid' => $currentPid,
                'datasetType' => 'Healthdata',
                'publisherId' => $team['pid'],
                'publisherName' => $team['name']
            ];

            $traserResponse = MMC::translateDataModelType(
                json_encode($payload),
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version')
            );

            if ($traserResponse['wasTranslated']) {
                $input['metadata']['original_metadata'] = $input['metadata']['metadata'];
                $input['metadata']['metadata'] = $traserResponse['metadata'];

                // Update the existing dataset parent record with incoming data
                $updateTime = now();
                $updatedDataset = $currDataset->update([
                    'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                        $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                    'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                        $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                    'updated' => $updateTime,
                    'pid' => $currentPid,
                    'create_origin' => (isset($applicationOverrideDefaultValues['create_origin']) ?
                        $applicationOverrideDefaultValues['create_origin'] : $input['create_origin']),
                    'status' => (isset($applicationOverrideDefaultValues['status']) ?
                        $applicationOverrideDefaultValues['status'] : $input['status']),
                    'is_cohort_discovery' => $isCohortDiscovery,
                ]);

                // Determine the last version of metadata
                $lastVersionNumber = $currDataset->lastMetadataVersionNumber()->version;

                $currentVersionCode = $this->formatVersion($lastVersionNumber + 1);
                $lastVersionCode = $this->formatVersion($lastVersionNumber);

                $lastMetadata = $currDataset->lastMetadata();

                //update the GWDM modified date and version
                $input['metadata']['metadata']['required']['modified'] = $updateTime;
                if(version_compare(Config::get('metadata.GWDM.version'), '1.0', '>')) {
                    if(version_compare($lastMetadata['gwdmVersion'], '1.0', '>')) {
                        $lastVersionCode = $lastMetadata['metadata']['required']['version'];
                    }
                }

                //update the GWDM revisions
                // NOTE: Calum 12/1/24
                //       - url set with a placeholder right now, should be revised before production
                //       - https://hdruk.atlassian.net/browse/GAT-3392
                $input['metadata']['metadata']['required']['revisions'][] = [
                    "url" => env('GATEWAY_URL') . '/dataset' .'/' . $id . '?version=' . $currentVersionCode,
                    'version' => $currentVersionCode
                ];

                $input['metadata']['gwdmVersion'] =  Config::get('metadata.GWDM.version');

                // Create new metadata version for this dataset
                $version = DatasetVersion::create([
                    'dataset_id' => $currDataset->id,
                    'metadata' => json_encode($input['metadata']),
                    'version' => ($lastVersionNumber + 1),
                ]);

                Auditor::log([
                    'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                        $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                    'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                        $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                    'action_type' => 'UPDATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Dataset ' . $id . ' with version ' . ($lastVersionNumber + 1) . ' updated',
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => Dataset::with('versions')->where('id', '=', $currDataset->id)->first(),
                ], Config::get('statuscodes.STATUS_OK.code'));
            } else {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed',
                    'details' => $traserResponse,
                ], 400);
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : null),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : null),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/integrations/datasets/{id}",
     *    operationId="patch_datasets_integrations",
     *    tags={"Datasets"},
     *    summary="IntegrationDatasetController@edit",
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
        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            if ($request->has('unarchive')) {
                $datasetModel = Dataset::withTrashed()
                    ->where(['id' => $id])
                    ->first();

                $this->checkAppCanHandleDataset($datasetModel->team_id, $request);

                if ($request['status'] !== Dataset::STATUS_ARCHIVED) {
                    if (in_array($request['status'], [
                        Dataset::STATUS_ACTIVE, Dataset::STATUS_DRAFT
                    ])) {
                        $datasetModel->status = $request['status'];
                        $datasetModel->deleted_at = null;
                        $datasetModel->save();

                        $metadata = DatasetVersion::withTrashed()->where('dataset_id', $id)
                            ->latest()->first();
                        $metadata->deleted_at = null;
                        $metadata->save();

                        Auditor::log([
                            'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                                $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                            'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                                $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                            'action_type' => 'UPDATE',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => 'Dataset ' . $id . ' marked as ' . strtoupper($request['status']) . ' updated',
                        ]);
                    } else {
                        $message = 'unknown status type';

                        Auditor::log([
                            'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                                $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                            'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                                $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                            'action_type' => 'EXCEPTION',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => $message,
                        ]);

                        throw new Exception($message);
                    }
                }
            } else {
                $datasetModel = Dataset::where(['id' => $id])
                    ->first();

                $this->checkAppCanHandleDataset($datasetModel->team_id, $request);

                if ($datasetModel['status'] === Dataset::STATUS_ARCHIVED) {
                    return response()->json([
                        'message' => 'status of an archived Dataset cannot be modified',
                    ], 400);
                }

                if (in_array($request['status'], [
                    Dataset::STATUS_ACTIVE, Dataset::STATUS_DRAFT
                ])) {
                    $previousDatasetStatus = $datasetModel->status;
                    $datasetModel->status = $request['status'];
                    $datasetModel->save();
                } else {
                    throw new Exception('unknown status type');
                }

                // TODO remaining edit steps e.g. if dataset appears in the request
                // body validate, translate if needed, update Mauro data model, etc.

                Auditor::log([
                    'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                        $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                    'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                        $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                    'action_type' => 'UPDATE',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => 'Dataset ' . $id . ' marked as ' . strtoupper($request['status']) . ' updated',
                ]);
            }

            return response()->json([
                'message' => 'success'
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/integrations/datasets/{id}",
     *      operationId="delete_datasets_integrations",
     *      summary="Delete a dataset",
     *      description="Delete a dataset",
     *      tags={"Datasets"},
     *      summary="IntegrationDatasetController@destroy",
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
        try {
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $dataset = Dataset::findOrFail($id);
            $this->checkAppCanHandleDataset($dataset->team_id, $request);

            MMC::deleteDataset($id, true);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dataset ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/integrations/datasets/test",
     *    operationId="integrations_datasets_test",
     *    tags={"Integrations datasets test"},
     *    summary="IntegrationDatasetController@datasetTest",
     *    description="Integrations datasets test",
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

    private function getVersion(int $version)
    {
        if($version > 999) {
            throw new Exception('too many versions');
        }

        $version = max(0, $version);

        $hundreds = floor($version / 100);
        $tens = floor(($version % 100) / 10);
        $units = $version % 10;

        $formattedVersion = "{$hundreds}.{$tens}.{$units}";

        return $formattedVersion;
    }

    private function extractMetadata(Mixed $metadata)
    {

        if(isset($metadata['metadata']['metadata'])) {
            $metadata = $metadata['metadata'];
        }

        // Pre-process check for incoming data from a resource that passes strings
        // when we expect an associative array. GMI passes strings, this
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


    private function mapCoverage(array $metadata, DatasetVersion $version): void
    {
        if (!isset($metadata['metadata']['coverage']['spatial'])) {
            return;
        }

        $coverage = strtolower($metadata['metadata']['coverage']['spatial']);
        $ukCoverages = SpatialCoverage::whereNot('region', 'Rest of the world')->get();
        $worldId = SpatialCoverage::where('region', 'Rest of the world')->first()->id;

        $matchFound = false;
        foreach ($ukCoverages as $c) {
            if (str_contains($coverage, strtolower($c['region']))) {

                DatasetVersionHasSpatialCoverage::updateOrCreate([
                    'dataset_version_id' => (int)$version['id'],
                    'spatial_coverage_id' => (int)$c['id'],
                ]);
                $matchFound = true;
            }
        }

        if (!$matchFound) {
            if (str_contains($coverage, 'united kingdom')) {
                foreach ($ukCoverages as $c) {
                    DatasetVersionHasSpatialCoverage::updateOrCreate([
                        'dataset_version_id' => (int)$version['id'],
                        'spatial_coverage_id' => (int)$c['id'],
                    ]);
                }
            } else {
                DatasetVersionHasSpatialCoverage::updateOrCreate([
                    'dataset_version_id' => (int)$version['id'],
                    'spatial_coverage_id' => (int)$worldId,
                ]);
            }
        }
    }

}
