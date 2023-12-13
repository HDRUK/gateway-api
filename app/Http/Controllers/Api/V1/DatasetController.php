<?php

namespace App\Http\Controllers\Api\V1;

use Mauro;
use Config;
use Exception;
use App\Models\Team;

use App\Models\User;
use App\Models\Dataset;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Jobs\TechnicalObjectDataStore;
use App\Models\DatasetHasNamedEntities;
use MetadataManagementController AS MMC;
use App\Http\Requests\Dataset\GetDataset;
use App\Http\Requests\Dataset\TestDataset;
use App\Http\Requests\Dataset\CreateDataset;
use App\Http\Requests\Dataset\UpdateDataset;
use App\Http\Requests\Dataset\EditDataset;

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
     *       name="sort",
     *       in="query",
     *       description="Field to sort by (default: 'created')",
     *       example="created",
     *       @OA\Schema(
     *          type="string",
     *          description="Field to sort by",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="direction",
     *       in="query",
     *       description="Sort direction ('asc' or 'desc', default: 'desc')",
     *       example="desc",
     *       @OA\Schema(
     *          type="string",
     *          enum={"asc", "desc"},
     *          description="Sort direction",
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

        $teamId = $request->query('team_id',null);
        $filterStatus = $request->query('status', null);

        $sort = $request->query('sort',"created:desc");   
        
        $tmp = explode(":", $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        $doSortFromMauro = str_contains($sortField,"properties/");

        if( $doSortFromMauro === false) {
            $allFields = collect(Dataset::first())->keys()->toArray();
            if(count($allFields) > 0 && !in_array($sortField, $allFields)){
                //if the field to be sorted is not a field in the model, then return a bad request
                return response()->json([
                        "message" => "Sorting on '".$sortField."' is not a valid field to sort on" 
                        ],400);                          
            }
        }

        $validDirections = ['desc', 'asc'];

        if(!in_array($sortDirection, $validDirections)){
            //if the sort direction is not desc or asc then return a bad request
            return response()->json([
                    "message" => "Sort direction must be either: " . 
                                implode(' OR ',$validDirections) . 
                                '. Not "' . $sortDirection .'"'
                    ],400);
        }

        

        //apply any initial filters to get initial datasets 
        $initialDatasets = Dataset::when($teamId, 
                                    function ($query) use ($teamId){
                                        return $query->where('team_id', '=', $teamId);
                                    })
                            ->when($request->has('withTrashed') || $filterStatus === 'ARCHIVED', 
                                    function ($query) {
                                        return $query->withTrashed();
                                    })
                            ->when($filterStatus, 
                                    function ($query) use ($filterStatus) {
                                        return $query->where('status', '=', $filterStatus);
                                    })
                            ->select(['id','datasetid','updated'])->get();


        $mauro = [];
        foreach ($initialDatasets as $dataset) {
            if($dataset->datasetid){
                $mauroDatasetIdMetadata = Mauro::getDatasetByIdMetadata($dataset->datasetid);
                $mauro[$dataset->id] = array_key_exists('items', $mauroDatasetIdMetadata) ? $mauroDatasetIdMetadata['items'] : [];
            }
            else{
                $mauro[$dataset->id] = [];
            }
        }

        // filtering by title
        $filterTitle = $request->query('title', null);
        if (!empty($filterTitle)) {
            $filterTitle = mb_strtolower($filterTitle);
            $matches = array();
            // iterate through mauro field of each dataset
            foreach ($initialDatasets as $dataset) {
                foreach ($mauro[$dataset->id] as $field) {
                    // find the title field in mauro data
                    if ($field['key'] === 'properties/summary/title') {
                        // if title matches filter, store the dataset id and mauro field
                        if (str_contains(mb_strtolower($field['value']), $filterTitle)) {
                            //$matches[$dataset['id']] = $dataset['mauro'];
                            $matches[] = $dataset->id;
                        }
                        break(1);
                    }
                }
            }
        }
        else{
            //otherwise select all the IDs originals fetched
            $matches = array_column($initialDatasets->toArray(),'id');
        }

        // perform query for the matching datasets and reattach the mauro field
        // rather than refetching it from mauro
        // can now do ordering and pagination
        $datasets = Dataset::whereIn('id', $matches)
                ->when($request->has('withTrashed') || $filterStatus === 'ARCHIVED', 
                    function ($query) {
                        return $query->withTrashed();
                    })
                ->when($doSortFromMauro===false,
                        function ($query) use ($sortField, $sortDirection) {
                            return $query->orderBy($sortField, $sortDirection);
                        })
                ->paginate(Config::get('constants.per_page'), ['*'], 'page');

        foreach ($datasets as $dataset) {
            $dataset['mauro'] = $mauro[$dataset->id];
        }
        
        if($doSortFromMauro) {
            //do sorting on mauro fields 
            $callBackSort = function ($dataset) use ($sortField) {
                $title = '';
                foreach ($dataset['mauro'] as $item) {
                    if ($item['key'] === $sortField) {
                        $title = $item['value'];
                        break;
                    }
                }
                return $title;
            };

            if($sortDirection === 'asc'){
                $sortedDatasets = $datasets->sortBy($callBackSort);
            }
            else{
                $sortedDatasets = $datasets->sortByDesc($callBackSort);
            }
            $sortedDatasets = $sortedDatasets->makeHidden(['dataset'])->values();

            //https://stackoverflow.com/questions/53384956/how-to-paginate-and-sort-results-in-laravel
            $datasets->setCollection($sortedDatasets);

        }

        return response()->json(
            $datasets
        );
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
        $counts = Dataset::when($teamId, 
                                    function ($query) use ($teamId){
                                        return $query->where('team_id', '=', $teamId);
                                    })
                            ->withTrashed()
                            ->select($field)
                            ->get()
                            ->groupBy($field)
                            ->map->count();

        return response()->json([
            "data" => $counts
            ]
        );
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
                ->with(['namedEntities'])
                ->first()
                ->toArray();

            if ($dataset['datasetid']) {
                $mauroDatasetIdMetadata = Mauro::getDatasetByIdMetadata($dataset['datasetid']);
                $dataset['mauro'] = array_key_exists('items', $mauroDatasetIdMetadata) ? $mauroDatasetIdMetadata['items'] : [];
            }

            $outputSchemaModel = $request->query('schema_model');
            $outputSchemaModelVersion = $request->query('schema_version');

            if($outputSchemaModel && $outputSchemaModelVersion){
                $translated = MMC::translateDataModelType(
                    $dataset['dataset'],
                    $outputSchemaModel,
                    $outputSchemaModelVersion,
                    env('GWDM'),
                    env('GWDM_CURRENT_VERSION'),
                );
                if($translated['wasTranslated']){
                    $dataset['dataset'] = json_encode($translated['metadata']);
                }
                else{
                    return response()->json([
                        'message' => 'failed to translate',
                        'details' => $translated
                    ], 400);
                }
            }
            elseif($outputSchemaModel){
                throw new Exception('You have given a schema_model but not a schema_version');
            }
            elseif($outputSchemaModelVersion){
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
     *             @OA\Property(property="label", type="string", example="label dataset for test"),
     *             @OA\Property(property="short_description", type="string", example="lorem ipsum"),
     *             @OA\Property(property="create_origin", type="string", example="MANUAL"),
     *             @OA\Property(property="dataset", type="array", @OA\Items())
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
            $mauro = null;
            $input = $request->all();

            $user = User::where('id', (int) $input['user_id'])->first()->toArray();
            $team = Team::where('id', (int) $input['team_id'])->first()->toArray();

            //send the payload to traser
            // - traser will return the input unchanged if the data is
            //   already in the GWDM with GWDM_CURRENT_VERSION
            // - if it is not, traser will try to work out what the metadata is
            //   and translate it into the GWDM
            // - otherwise traser will return a non-200 error 
            $traserResponse = MMC::translateDataModelType(
                json_encode($input['dataset']),
                env('GWDM'),
                env('GWDM_CURRENT_VERSION')
            );


            if($traserResponse['wasTranslated']){
                $input['metadata']['original_metadata'] = $input['dataset']['metadata'];
                $input['dataset']['metadata'] = $traserResponse['metadata'];

                $mauro = MMC::createMauroDataModel($user, $team, $input);

                if (!empty($mauro)) {

                    if($mauro['DataModel']['responseStatus'] !== 201){

                        return response()->json([
                            'message' => 'Failed to create mauroDataModel',
                            'details' => $mauro['DataModel']['responseJson'],
                        ]);
                    }

                    $mauroDatasetId = (string) $mauro['DataModel']['responseJson']['id'];

                    $dataset = MMC::createDataset([
                        'datasetid' => $mauroDatasetId,
                        'label' => $input['label'],
                        'short_description' => $input['short_description'],
                        'user_id' => $input['user_id'],
                        'team_id' => $input['team_id'],
                        'dataset' => json_encode($input['dataset']),
                        'created' => now(),
                        'updated' => now(),
                        'submitted' => now(),
                        'pid' => (string) Str::uuid(),
                        'create_origin' => $input['create_origin'],
                        'status' => $input['status'],
                    ]);                    
                    $dId = $dataset->id; 

                    //overwrite whatever gatewayId has been set
                    // - this logic could be put somewhere else?
                    // - there may be some other logic/fields to be filled here?
                    //   e.g. revisions and versions? 
                    $input['dataset']['metadata']['required']['gatewayId'] = strval($dId);
                    


                    // Dispatch this potentially lengthy subset of data
                    // to a technical object data store job - API doesn't
                    // care if it exists or not. We leave that determination to
                    // the service itself.
                    // and not found `extracted_terms`
                    TechnicalObjectDataStore::dispatch(
                        (string) $mauroDatasetId,
                        base64_encode(gzcompress(gzencode(json_encode($input['dataset']['metadata'])), 6)),
                        false
                    );

                    // Only finalise when:
                    //      1. Dataset is onboarded via automation (Applications or Federation)
                    //      2. Dataset is onboarded via manual form and status is ACTIVE
                    // otherwise, we assume the dataset is still being configured
                    if ($dataset->shouldFinalise()) {
                        $versioning = Mauro::finaliseDataModel($mauroDatasetId);
                        $dataset->update([
                            'version' => (string) $versioning['documentationVersion'],
                            'status' => Dataset::STATUS_ACTIVE,
                        ]);
                    }

                    return response()->json([
                        'message' => 'created',
                        'data' => $dId,
                    ], 201);
                }
                throw new NotFoundException('Mauro Data Mapper folder id for team ' . $input['team_id'] . ' not found');
            }
            else{
                return response()->json([
                    'message' => 'dataset is in an unknown format and cannot be processed',
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
     *             @OA\Property(property="label", type="string", example="label dataset for test"),
     *             @OA\Property(property="short_description", type="string", example="lorem ipsum"),
     *             @OA\Property(property="create_origin", type="string", example="MANUAL"),
     *             @OA\Property(property="dataset", type="array", @OA\Items())
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

            $user = User::where('id', (int) $input['user_id'])->first()->toArray();
            $team = Team::where('id', (int) $input['team_id'])->first()->toArray();
            $currDataset = Dataset::where('id', $id)->first()->toArray();
            $currentPid = $currDataset['pid'];
            $currentDatasetId = $currDataset['datasetid'];

            // First validate the incoming schema to ensure it's in GWDM format
            // if not, attempt to translate prior to saving
            $validateDataModelType = MMC::validateDataModelType(
                json_encode($input['dataset']),
                env('GWDM'),
                env('GWDM_CURRENT_VERSION')
            );

            if ($validateDataModelType) {
                $duplicateDataModel = Mauro::duplicateDataModel($currentDatasetId);
                $newDatasetId = (string) $duplicateDataModel['id'];
                MMC::updateDataModel($user, $team, $input, $newDatasetId);

                $dataset = MMC::createDataset([
                    'datasetid' => $newDatasetId,
                    'label' => $input['label'],
                    'short_description' => $input['short_description'],
                    'user_id' => $input['user_id'],
                    'team_id' => $input['team_id'],
                    'dataset' => json_encode($input['dataset']),
                    'created' => now(),
                    'updated' => now(),
                    'submitted' => now(),
                    'pid' => $currentPid,
                    'create_origin' => $input['create_origin'],
                    'status' => $input['status'],
                ]);
                $dId = $dataset->id;

                // Dispatch this potentially lengthy subset of data
                // to a technical object data store job - API doesn't
                // care if it exists or not. We leave that determination to
                // the service itself.
                TechnicalObjectDataStore::dispatch(
                    $newDatasetId,
                    base64_encode(gzcompress(gzencode(json_encode($input['dataset']['metadata'])), 6)),
                    true
                );

                if ($dataset->shouldFinalise()) {
                    $versioning = Mauro::finaliseDataModel($newDatasetId, 'minor');
                    $dataset->update([
                        'version' => (string) $versioning['modelVersion'],
                        'status' => Dataset::STATUS_ACTIVE,
                    ]);
                }

                $dataset->delete();
                MMC::deleteFromElastic($id);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => Dataset::where('id', '=', $dId)->first(),
                ], Config::get('statuscodes.STATUS_OK.code'));
            } else {
                // Incoming dataset is not in GWDM format, so at this point we
                // need to translate it
                $response = MMC::translateDataModelType(
                    json_encode($input['dataset']),
                    env('GWDM'),
                    env('GWDM_CURRENT_VERSION'),
                    env('HDRUK'),
                    // TODO
                    // 
                    // The following is hardcoded for now - but needs to be
                    // more intelligent in the future. Need a solution for
                    // not working on assumptions. Theoretically, we can 
                    // use the incoming version, but needs confirmation
                    '2.1.2'
                );

                if (!empty($response)) {
                    $input['dataset'] = $response;
                    $duplicateDataModel = Mauro::duplicateDataModel($currentDatasetId);
                    $newDatasetId = (string) $duplicateDataModel['id'];
                    MMC::updateDataModel($user, $team, $input, $newDatasetId);

                    $dataset = MMC::createDataset([
                        'datasetid' => $newDatasetId,
                        'label' => $input['label'],
                        'short_description' => $input['short_description'],
                        'user_id' => $input['user_id'],
                        'team_id' => $input['team_id'],
                        'dataset' => json_encode($response),
                        'created' => now(),
                        'updated' => now(),
                        'submitted' => now(),
                        'pid' => $currentPid,
                        'create_origin' => $input['create_origin'],
                        'status' => $input['status'],
                    ]);
                    $dId = $dataset->id;

                    // Dispatch this potentially lengthy subset of data
                    // to a technical object data store job - API doesn't
                    // care if it exists or not. We leave that determination to
                    // the service itself.
                    TechnicalObjectDataStore::dispatch(
                        $dId,
                        base64_encode(gzcompress(gzencode(json_encode($response)), 6)),
                        true
                    );

                    if ($dataset->shouldFinalise()) {
                        $versioning = Mauro::finaliseDataModel($newDatasetId, 'minor');
                        $dataset->update([
                            'version' => (string) $versioning['modelVersion'],
                            'status' => Dataset::STATUS_ACTIVE,
                        ]);
                    }

                    $dataset->delete();
                    MMC::deleteFromElastic($id);

                    return response()->json([
                        'message' => Config::get('statuscodes.STATUS_OK.message'),
                        'data' => Dataset::where('id', '=', $dId)->first(),
                    ], Config::get('statuscodes.STATUS_OK.code'));
                }

                // Fail
                return response()->json([
                    'message' => 'dataset is in an unknown format and cannot be processed',
                ], 400);
            }

            throw new NotFoundException('Mauro Data Mapper folder id for team ' . $input['team_id'] . ' not found');
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
                if ($request['status'] === 'ACTIVE') {
                    $datasetModel->status = Dataset::STATUS_ACTIVE;
                }
                elseif ($request['status'] === 'DRAFT') {
                    $datasetModel->status = Dataset::STATUS_DRAFT;
                }

                $datasetModel->deleted_at = null;
                $datasetModel->save();
                
                $dataset = $datasetModel->toArray();
                Mauro::restoreDataModel($dataset['datasetid']);

                if ($request['status'] === 'ACTIVE') {
                    $mauroModel = Mauro::getDatasetByIdMetadata($dataset['datasetid']);

                    MMC::reindexElasticFromModel($mauroModel, $dataset['datasetid']);
                }

            } else {
                $dataset = Dataset::where(['id' => $id])->first()->toArray();
            }

            // TODO remaining edit steps e.g. if dataset appears in the request 
            // body validate, translate if needed, update Mauro data model, etc.

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
            $dataset = Dataset::where('id', (int) $id)->first();

            if (isset($dataset->datasetid)) {
                Mauro::deleteDataModel($dataset->datasetid);
            }

            $dataset->deleted_at = Carbon::now();
            $dataset->status = Dataset::STATUS_ARCHIVED;
            $dataset->save();

            // error: The client noticed that the server is not Elasticsearch and we do not support this unknown product
            MMC::deleteFromElastic($id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/integrations/datasets/test",
     *    operationId="integrations_datasets_test",
     *    tags={"Integrations datasets test"},
     *    summary="DatasetController@datasetTest",
     *    description="Integrations datasets test",
     *    security={{"bearerAppAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass datasets payload",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="label", type="string", example="label dataset for test"),
     *             @OA\Property(property="short_description", type="string", example="lorem ipsum"),
     *             @OA\Property(property="dataset", type="array", @OA\Items())
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
                json_encode($input['dataset']),
                env('GWDM'),
                env('GWDM_CURRENT_VERSION')
            );

            if ($traserResponse['wasTranslated']) {
                return response()->json([
                    'message' => 'success',
                    'payload_received' => $input,
                ], 200);
            }

            return response()->json([
                'message' => 'dataset is in an unknown format and cannot be processed',
                'details' => $traserResponse,
                'payload_received' => $input,
            ], 400);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
