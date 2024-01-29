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
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Jobs\TermExtraction;
use MetadataManagementController AS MMC;
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
        $matches = [];
        $teamId = $request->query('team_id',null);
        $filterStatus = $request->query('status', null);
        $datasetId = $request->query('dataset_id', null);

        $sort = $request->query('sort',"created:desc");   
        
        $tmp = explode(":", $sort);
        $sortField = $tmp[0];
        $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

        $allFields = collect(Dataset::first())->keys()->toArray();
        if (count($allFields) > 0 && !in_array($sortField, $allFields)) {
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
        $datasets = Dataset::with(['versions' => fn($version) => $version->withTrashed()->latest()])
            ->whereIn('id', $matches)
            ->when($request->has('withTrashed') || $filterStatus === 'ARCHIVED', 
                function ($query) {
                    return $query->withTrashed();
                })
            ->when(true,
                    function ($query) use ($sortField, $sortDirection) {
                        return $query->orderBy($sortField, $sortDirection);
                    })
            ->paginate($perPage, ['*'], 'page');

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
                    env('GWDM'),
                    env('GWDM_CURRENT_VERSION'),
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
            $team = Team::where('id', (int) $input['team_id'])->first()->toArray();

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
                "publisherName"=>$team['name'],
            ];

            $traserResponse = MMC::translateDataModelType(
                json_encode($payload),
                env('GWDM'),
                env('GWDM_CURRENT_VERSION')
            );

            if ($traserResponse['wasTranslated']) {
                $input['metadata']['original_metadata'] = $input['metadata']['metadata'];
                $input['metadata']['metadata'] = $traserResponse['metadata'];

                $mongo_object_id = array_key_exists('mongo_object_id', $input) ? $input['mongo_object_id'] : null;
                $mongo_id = array_key_exists('mongo_id', $input) ? $input['mongo_id'] : null;
                $mongo_pid = array_key_exists('mongo_pid', $input) ? $input['mongo_pid'] : null;
                $datasetid = array_key_exists('datasetid', $input) ? $input['datasetid'] : null;

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
                    'pid' => (string) Str::uuid(),
                    'create_origin' => $input['create_origin'],
                    'status' => $input['status'],
                ]);

                //create a new 'required' section for the metadata to be saved
                // - otherwise this section is filled with placeholders by all translations to GWDM
                $required = [
                    'gatewayId' => strval($dataset->id),
                    'gatewayPid' => $dataset->pid,
                    'issued' => $dataset->created,
                    'modified' => $dataset->updated,
                    'revisions' => [],
                ];
                $input['metadata']['metadata']['required'] = $required;

                //force correct publisher field based on the team
                $publisher = [
                    'publisherId' => $team['pid'],
                    'publisherName' => $team['name'],
                ];
                $input['metadata']['metadata']['summary']['publisher'] = $publisher;


                $version = MMC::createDatasetVersion([
                    'dataset_id' => $dataset->id,
                    'metadata' => json_encode($input['metadata']),
                    'version' => 1,
                ]);

                // Dispatch term extraction to a subprocess as it may take some time
                TermExtraction::dispatch(
                    $dataset->id,
                    base64_encode(gzcompress(gzencode(json_encode($input['metadata'])), 6))
                );

                return response()->json([
                    'message' => 'created',
                    'data' => $dataset->id,
                    'version' => $version->id,
                ], 201);
            }
            else {
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

            $user = User::where('id', (int) $input['user_id'])->first();
            $team = Team::where('id', (int) $input['team_id'])->first();
            $currDataset = Dataset::where('id', $id)->first();
            $currentPid = $currDataset->pid;


            $traserResponse = MMC::translateDataModelType(
                json_encode($input['metadata']),
                env('GWDM'),
                env('GWDM_CURRENT_VERSION')
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
                ]);

                // Determine the last version of metadata
                $lastVersionNumber = $currDataset->lastMetadataVersionNumber()->version;
     
                //update the GWDM modified date
                $input['metadata']['metadata']['required']['modified'] = $updateTime;

                //update the GWDM revisions
                // NOTE: Calum 12/1/24
                //       - url set with a placeholder right now, should be revised before production
                //       - https://hdruk.atlassian.net/browse/GAT-3392
                $input['metadata']['metadata']['required']['revisions'][] = [
                    "url"=>"https://placeholder.blah/".$currentPid."?version=".$lastVersionNumber, 
                    "version"=>$lastVersionNumber
                ];

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
                    $row = [
                        $metadata['metadata']['summary']['title'] !== null ? $metadata['metadata']['summary']['title'] : '',
                        $metadata['metadata']['summary']['publisher']['publisherName'] !== null ? $metadata['metadata']['summary']['publisher']['publisherName'] : '',
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
                'message' => 'metadata is in an unknown format and cannot be processed',
                'details' => $traserResponse,
                'payload_received' => $input,
            ], 400);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
