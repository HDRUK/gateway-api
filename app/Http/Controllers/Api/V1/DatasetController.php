<?php

namespace App\Http\Controllers\Api\V1;

use Mauro;
use Config;
use Exception;

use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Models\NamedEntities;
use App\Models\DatasetVersion;
use App\Models\DatasetHasSpatialCoverage;
use App\Models\SpatialCoverage;

use App\Jobs\TermExtraction;
use MetadataManagementController AS MMC;

use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Http\Requests\Dataset\GetDataset;
use App\Http\Requests\Dataset\TestDataset;
use App\Http\Requests\Dataset\CreateDataset;
use App\Http\Requests\Dataset\UpdateDataset;
use App\Http\Requests\Dataset\EditDataset;

use Illuminate\Support\Facades\Http;

class DatasetController extends Controller
{
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
        $matches = [];
        $teamId = $request->query('team_id',null);
        $filterStatus = $request->query('status', null);
        $datasetId = $request->query('dataset_id', null);
        $mongoPId = $request->query('mongo_pid', null);

        $sort = $request->query('sort',"created:desc");   
        
        $tmp = explode(":", $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        $sortOnMetadata = str_starts_with($sortField,"metadata.");

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
                "message" => "Sort direction must be either: " . 
                    implode(' OR ',$validDirections) . 
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
        })->when($request->has('withTrashed') || $filterStatus === 'ARCHIVED', 
            function ($query) {
                return $query->withTrashed();
        })->when($filterStatus, 
            function ($query) use ($filterStatus) {
                return $query->where('status', '=', $filterStatus);
        })->select(['id', 'updated'])->get();

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
        $datasets = Dataset::with('latestVersion') 
            ->whereIn('id', $matches)
            ->when($request->has('withTrashed') || $filterStatus === 'ARCHIVED', 
                function ($query) {
                    return $query->withTrashed();
                })
            ->when($sortOnMetadata, 
                fn($query) => $query->orderByMetadata($sortField, $sortDirection),
                fn($query) => $query->orderBy($sortField, $sortDirection)
            )
            ->with(['versions' => function ($query) {
                $query->latest()->take(1);
            }])
            ->paginate($perPage, ['*'], 'page');


        return response()->json(
            $datasets
        );
    }

    public function updateMetadataLinkages(Request $request, string $pid): JsonResponse
    {
        $dataset =  Dataset::where("pid",$pid)->first();
        $metadata = null;
        $version = $request->query("version",null);
        if($version){
            $metadata = $dataset->getMetadataVersion((int)$version);
        }
        else{
            $metadata = $dataset->latestMetadata();
        }
        $metadata = $metadata->metadata['metadata'];
        $updatedLinkage = $request->all();
        $updatedMetadata = $metadata;
        $updatedMetadata['linkage'] = $updatedLinkage;

        $updateRequest = new UpdateDataset();
        $updateRequest->merge(
            [
                "metadata"=>["metadata"=>$updatedMetadata],
                "team_id"=>$dataset->team_id,
                "user_id"=>$dataset->user_id,
                "create_origin"=>$dataset->create_origin,
                "status"=>$dataset->status
            ]
        );
        return $this->update($updateRequest,$dataset->id);
    }


    public function teamDatasetIds(Request $request, int $id): JsonResponse
    {
        $datasets = Dataset::where("team_id",$id)
            ->pluck("pid");

        return response()->json(["data"=>$datasets]);
    }

    public function getAllDatasetPids(Request $request): JsonResponse
    {
        $pids= Dataset::pluck("pid");
        return response()->json(["data"=>$pids]);
    }

    public function getAllDatasetLinkages(Request $request): JsonResponse
    {
        $version = $request->query("version",null);

        $whereCommand =  "WHERE (dataset_id, version) IN (
                SELECT dataset_id, MAX(version) AS max_version
                FROM dataset_versions
                GROUP BY dataset_id
            )";
        if($version){
            $whereCommand = "WHERE version=".$version;
        }

        $latestVersions = \DB::select("
            SELECT (SELECT pid FROM datasets WHERE id = dataset_versions.dataset_id) AS pid, 
                    JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.linkage') AS linkages,
                    JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.title') AS title
            FROM dataset_versions
        ".$whereCommand);
        $data = [];
        foreach ($latestVersions as $version) {
            $linkages = json_decode($version->linkages);
            if ($linkages !== null) {
                $data[] = [
                    "linkages" => $linkages,
                    "pid" => $version->pid,
                    "title" => json_decode($version->title)
                ];
            }
        }

        return response()->json(["data"=>$data]);
    }

    public function getAllDatasetTitles(Request $request): JsonResponse
    {
        $data = $this->getAllDatasetMetadata("summary.title");
        return response()->json(["data"=>$data]);
    }

    private function getAllDatasetMetadata(string $path)
    {
        $latestVersions = \DB::select("
            SELECT (SELECT pid FROM datasets WHERE id = dataset_versions.dataset_id) AS pid, 
                    JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.".$path."') AS value
            FROM dataset_versions
            WHERE (dataset_id, version) IN (
                SELECT dataset_id, MAX(version) AS max_version
                FROM dataset_versions
                GROUP BY dataset_id
            )
        ");
        $data = [];
        foreach ($latestVersions as $version) {
            $value = json_decode($version->value);
            if ($value !== null) {
                $data[] = [
                    "value" => $value,
                    "pid" => $version->pid,
                ];
            }
        }
        return $data;
    }

    public function metadataByPid(Request $request, string $pid): JsonResponse
    {
        $metadata = Dataset::where("pid",$pid)->first()->latestMetadata();
        return response()->json(["data"=>$metadata->metadata]);
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
        $teamId = $request->query('team_id',null);
        $counts = Dataset::when($teamId, function ($query) use ($teamId) {
            return $query->where('team_id', '=', $teamId);
        })->withTrashed()
            ->select($field)
            ->get()
            ->groupBy($field)
            ->map->count();

        return response()->json([
            "data" => $counts
        ]);
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
            $dataset = Dataset::where(['id' => $id])
                ->with(['namedEntities', 'collections'])
                ->first();

            $outputSchemaModel = $request->query('schema_model');
            $outputSchemaModelVersion = $request->query('schema_version');

            // Return the latest metadata for this dataset
            if (!($outputSchemaModel && $outputSchemaModelVersion)) {
                $version = $dataset->latestVersion();
                if ($version) {
                    $dataset->versions[] = $version;
                }
            }            

            if ($outputSchemaModel && $outputSchemaModelVersion) {
                $version = $dataset->latestVersion();

                $translated = MMC::translateDataModelType(
                    $version->metadata,
                    $outputSchemaModel,
                    $outputSchemaModelVersion,
                    Config::get('metadata.GWDM.name'),
                    Config::get('metadata.GWDM.version'),
                );

                if ($translated['wasTranslated']) {
                    $version->metadata = json_decode(json_encode($translated['metadata]']));
                    $dataset->versions[] = $version;
                }
                else {
                    return response()->json([
                        'message' => 'failed to translate',
                        'details' => $translated
                    ], 400);
                }
            }
            elseif ($outputSchemaModel) {
                throw new Exception('You have given a schema_model but not a schema_version');
            }
            elseif ($outputSchemaModelVersion) {
                throw new Exception('You have given a schema_version but not schema_model');
            }
            
            return response()->json([
                'message' => 'success',
                'data' => $dataset,
            ], 200);

        } catch (Exception $e) {
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
        try {
            $input = $request->all();

            $teamId = (int)$input['team_id'];

            $team = Team::where('id', $teamId)->first()->toArray();
            $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ? $input['is_cohort_discovery'] : false;

            $input['metadata'] = $this->extractMetadata($input['metadata']);

            //send the payload to traser
            // - traser will return the input unchanged if the data is
            //   already in the GWDM with GWDM_CURRENT_VERSION
            // - if it is not, traser will try to work out what the metadata is
            //   and translate it into the GWDM
            // - otherwise traser will return a non-200 error 

            $payload = $input['metadata'];
            $payload['extra'] = [
                "id"=>"placeholder",
                "pid"=>"placeholder",
                "datasetType"=>"Healthdata",
                "publisherId"=>$team['pid'],
                "publisherName"=>$team['name']
            ];

            $traserResponse = MMC::translateDataModelType(
                json_encode($payload),
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version'),
            );

            if ($traserResponse['wasTranslated']) {
                $input['metadata']['original_metadata'] = $input['metadata']['metadata'];
                $input['metadata']['metadata'] = $traserResponse['metadata'];

                $mongo_object_id = array_key_exists('mongo_object_id', $input) ? $input['mongo_object_id'] : null;
                $mongo_id = array_key_exists('mongo_id', $input) ? $input['mongo_id'] : null;
                $mongo_pid = array_key_exists('mongo_pid', $input) ? $input['mongo_pid'] : null;
                $datasetid = array_key_exists('datasetid', $input) ? $input['datasetid'] : null;

                $pid = array_key_exists('pid', $input) ? $input['pid'] : (string) Str::uuid();

                $dataset = MMC::createDataset([
                    'user_id' => $input['user_id'],
                    'team_id' => $input['team_id'],
                    'mongo_object_id' => $mongo_object_id,
                    'mongo_id' => $mongo_id,
                    'mongo_pid' => $mongo_pid,
                    'datasetid' => $datasetid,
                    'created' => now(),
                    'updated' => now(),
                    'submitted' => now(),
                    'pid' => $pid,
                    'create_origin' => $input['create_origin'],
                    'status' => $input['status'],
                    'is_cohort_discovery' => $isCohortDiscovery,
                ]);

    
                $publisher = null;
                $required = [
                        'gatewayId' => strval($dataset->id), //note: do we really want this in the GWDM?
                        'gatewayPid' => $dataset->pid,
                        'issued' => $dataset->created,
                        'modified' => $dataset->updated,
                        'revisions' => []
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
                if(version_compare(Config::get('metadata.GWDM.version'),"1.1","<")){
                    $publisher = [
                        'publisherId' => $team['pid'],
                        'publisherName' => $team['name'],
                    ];
                } else{
                    $version = $this->getVersion(1);
                    if(array_key_exists( 'version', $input['metadata']['metadata']['required'])){
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
                $this->mapCoverage($input['metadata'], $dataset);

                // Dispatch term extraction to a subprocess as it may take some time
                //TermExtraction::dispatch(
                //    $dataset->id,
               //     base64_encode(gzcompress(gzencode(json_encode($input['metadata'])), 6))
                //);

                return response()->json([
                    'message' => 'created',
                    'data' => $dataset->id,
                    'version' => $version->id,
                ], 201);
            }
            else {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed',
                    'details' => $traserResponse,
                ], 400);
            }
        } catch (Exception $e) {
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
        try {
            $input = $request->all();

            $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ? $input['is_cohort_discovery'] : false;

            $teamId = (int)$input['team_id'];
            $userId = (int)$input['user_id'];

            $user = User::where('id', $userId)->first();
            $team = Team::where('id', $teamId)->first();
            $currDataset = Dataset::where('id', $id)->first();
            $currentPid = $currDataset->pid;

            $input['metadata'] = $this->extractMetadata($input['metadata']);

            $payload = $input['metadata'];
            $payload['extra'] = [
                "id"=>$id,
                "pid"=>$currentPid,
                "datasetType"=>"Healthdata",
                "publisherId"=>$team['pid'],
                "publisherName"=>$team['name']
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
                    'user_id' => $input['user_id'],
                    'team_id' => $input['team_id'],
                    'updated' => $updateTime,
                    'pid' => $currentPid,
                    'create_origin' => $input['create_origin'],
                    'status' => $input['status'],
                    'is_cohort_discovery' => $isCohortDiscovery,
                ]);

                // Determine the last version of metadata
                $lastVersionNumber = $currDataset->lastMetadataVersionNumber()->version;

                $currentVersionCode = $this->getVersion($lastVersionNumber + 1);
                $lastVersionCode = $this->getVersion($lastVersionNumber);

                $lastMetadata = $currDataset->lastMetadata();
     
                //update the GWDM modified date and version
                $input['metadata']['metadata']['required']['modified'] = $updateTime;
                if(version_compare(Config::get('metadata.GWDM.version'),"1.0",">")){   
                    if(version_compare($lastMetadata['gwdmVersion'],"1.0",">")){
                        $lastVersionCode = $lastMetadata['metadata']['required']['version'];
                    }
                }
                
                //update the GWDM revisions
                // NOTE: Calum 12/1/24
                //       - url set with a placeholder right now, should be revised before production
                //       - https://hdruk.atlassian.net/browse/GAT-3392
                $input['metadata']['metadata']['required']['revisions'][] = [
                    "url"=>"https://placeholder.blah/".$currentPid."?version=".$lastVersionCode, 
                    "version"=>$lastVersionCode
                ];

                $input['metadata']['gwdmVersion'] =  Config::get('metadata.GWDM.version');

                // Create new metadata version for this dataset
                $version = DatasetVersion::create([
                    'dataset_id' => $currDataset->id,
                    'metadata' => json_encode($input['metadata']),
                    'version' => ($lastVersionNumber + 1),
                ]);


                MMC::reindexElastic($currDataset->id);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => Dataset::with('versions')->where('id', '=', $currDataset->id)->first(),
                ], Config::get('statuscodes.STATUS_OK.code'));
            } 
            else {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed',
                    'details' => $traserResponse,
                ], 400);
            }
        } catch (Exception $e) {
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
        try {
            if ($request->has('unarchive')) {
                $datasetModel = Dataset::withTrashed()
                    ->where(['id' => $id])
                    ->first();

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

                        if ($request['status'] === Dataset::STATUS_ACTIVE) {
                            MMC::reindexElastic($id);
                        }
                    } else {
                        throw new Exception('unknown status type');
                    }
                }
            } else {
                $datasetModel = Dataset::where(['id' => $id])
                    ->first();

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
            }

            return response()->json([
                'message' => 'success'
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/datasets/{id}",
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
        try {
            MMC::deleteDataset($id);
            MMC::deleteFromElastic($id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function destroyByPid(Request $request, string $pid) // softdelete
    {
        $dataset = Dataset::where('pid', "=", $pid)->first();
        return $this->destroy($request,$dataset->id);
    }

    public function updateByPid(UpdateDataset $request, string $pid)
    {
        $dataset = Dataset::where('pid', "=", $pid)->first();
        return $this->update($request,$dataset->id);
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
        $teamId = $request->query('team_id',null);
        $datasets = Dataset::when($teamId, function ($query) use ($teamId){
            return $query->where('team_id', '=', $teamId);
        });

        $results = $datasets->select('datasets.*')->get();

        // collect all the required information
        foreach ($results as $dataset) {
            $dataset['metadata'] = $dataset->latestVersion();
        }

        // callback function that writes to php://output
        $response = new StreamedResponse(
            function() use ($results) {

                // Open output stream
                $handle = fopen('php://output', 'w');
                
                $headerRow = ['Title', 'Publisher name', 'Last Activity', 'Method of dataset creation', 'Status', 'Metadata detail'];

                // Add CSV headers
                fputcsv($handle, $headerRow);
        
                // add the given number of rows to the file.
                foreach ($results as $rowDetails) {
                    $metadata = $rowDetails['metadata']['metadata'];

                    $publisherName = $metadata['metadata']['summary']['publisher'];
                    if(version_compare(Config::get('metadata.GWDM.version'),"1.1","<")){
                        $publisherName = $publisherName['publisherName'];
                    }else{
                        $publisherName = $publisherName['name'];
                    }

                    $row = [
                        $metadata['metadata']['summary']['title'] !== null ? $metadata['metadata']['summary']['title'] : '',
                        $publisherName !== null ? $publisherName : '',
                        $rowDetails['metadata']['updated_at'] !== null ? $rowDetails['metadata']['updated_at'] : '',
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
        $response->headers->set('Cache-Control','max-age=0');
        
        return $response;
    }

    private function getVersion(int $version){
        if($version>999) throw new Exception("too many versions");

        $version = max(0, $version);

        $hundreds = floor($version / 100);
        $tens = floor(($version % 100) / 10);
        $units = $version % 10;

        $formattedVersion = "{$hundreds}.{$tens}.{$units}";

        return $formattedVersion;
    }

    private function extractMetadata (Mixed $metadata){

        // Pre-process check for incoming data from a resource that passes strings
        // when we expect an associative array. FMA passes strings, this
        // is a safe-guard to ensure execution is unaffected by other data types.
        if (isset($metadata['metadata'])) {
            if (is_string($metadata['metadata'])) {
                $tmpMetadata['metadata'] = json_decode($metadata['metadata'], true);
                unset($metadata['metadata']);
                $metadata = $tmpMetadata;
            }
        } else if (is_string($metadata)) {
            $tmpMetadata['metadata'] = json_decode($metadata, true);
            unset($metadata);
            $metadata = $tmpMetadata;
        }
        return $metadata;
    }


    private function mapCoverage(array $metadata, Dataset $dataset): void 
    {
        $coverage = strtolower($metadata['metadata']['coverage']['spatial']);
        $ukCoverages = SpatialCoverage::whereNot('region', 'Rest of the world')->get();
        $worldId = SpatialCoverage::where('region', 'Rest of the world')->first()->id;

        $matchFound = false;
        foreach ($ukCoverages as $c) {
            if (str_contains($coverage, strtolower($c['region']))) {
                DatasetHasSpatialCoverage::updateOrCreate([
                    'dataset_id' => (int) $dataset['id'],
                    'spatial_coverage_id' => (int) $c['id'],
                ]);
                $matchFound = true;
            }
        }

        if (!$matchFound) {
            if (str_contains($coverage, 'united kingdom')) {
                foreach ($ukCoverages as $c) {
                    DatasetHasSpatialCoverage::updateOrCreate([
                        'dataset_id' => (int) $dataset['id'],
                        'spatial_coverage_id' => (int) $c['id'],
                    ]);
                }
            } else {
                DatasetHasSpatialCoverage::updateOrCreate([
                    'dataset_id' => (int) $dataset['id'],
                    'spatial_coverage_id' => (int) $worldId,
                ]);
            }
        }
    }

}
