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
use App\Http\Traits\TrimPayload;
use App\Http\Traits\CheckAccess;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\MetadataOnboard;
use App\Http\Traits\MetadataVersioning;
use App\Models\Traits\ModelHelpers;
use Maatwebsite\Excel\Facades\Excel;
use MetadataManagementController as MMC;
use App\Http\Traits\DatasetsV2Helpers;
use App\Http\Traits\RequestTransformation;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Requests\V2\Dataset\GetDataset;
use App\Http\Requests\V2\Dataset\DeleteDataset;
use App\Http\Requests\V2\Dataset\EditTeamDataset;
use App\Http\Requests\V2\Dataset\CreateTeamDataset;
use App\Http\Requests\V2\Dataset\UpdateTeamDataset;
use App\Exports\DatasetStructuralMetadataExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeamDatasetController extends Controller
{
    use MetadataVersioning;
    use GetValueByPossibleKeys;
    use MetadataOnboard;
    use CheckAccess;
    use ModelHelpers;
    use RequestTransformation;
    use DatasetsV2Helpers;
    use TrimPayload;

    /**
     * @OA\Get(
     *    path="/api/v2/teams/{teamId}/datasets/status/{status}",
     *    operationId="fetch_team_datasets_status",
     *    tags={"Datasets"},
     *    summary="TeamDatasetController@indexStatus",
     *    description="Returns a list of a team's datasets with the given status",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="ID of the team to filter by",
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
    public function indexStatus(Request $request, int $teamId, ?string $status = 'active'): JsonResponse
    {
        $this->checkAccess($request->all(), $teamId, null, 'team', $request->header());

        try {
            $withMetadata = $request->boolean('with_metadata', true);

            $perPage = request('per_page', Config::get('constants.per_page'));

            $filterTitle = $request->query('title', null);

            $teamDatasetIds = Dataset::where(['team_id' => $teamId, 'status' => strtoupper($status)])->pluck("id");
            $datasetIds = [];
            // If we've received a 'title' for the search, then only return
            // datasets that match that title
            if (!empty($filterTitle)) {
                foreach ($teamDatasetIds as $d) {
                    $version = DatasetVersion::where('dataset_id', $d)
                    ->filterTitle($filterTitle)
                    ->select('dataset_id')
                    ->first();

                    if ($version) {
                        $datasetIds[] = $d;
                    }
                }
            } else {
                $datasetIds = $teamDatasetIds;
            }
            // Fetch metadata
            $datasets = Dataset::whereIn("id", $datasetIds)
                ->when($withMetadata, fn ($query) => $query->with('latestMetadata'))
                ->applySorting()
                ->paginate((int) $perPage, ['*'], 'page');

            $metadataMissing = false;


            foreach ($datasets as $key => & $d) {

                if (empty($d->latestMetadata) || !isset($d->latestMetadata['metadata'])) {
                    // this needs refactoring to mark the metadata as corrupt or missing and
                    // then set them as draft and alert the FE
                    unset($datasets[$key]);
                    $metadataMissing = true;
                    continue;
                }

                $latestVersion = $d->latestVersion(['updated_at']);
                if ($latestVersion) {
                    $d->updated_at = $latestVersion->updated_at;
                }

                $miniMetadata = $this->trimDatasets($d->latestMetadata['metadata'], [
                    'summary',
                    'required',
                ]);

                // latestMetadata is a relation and cannot be assigned at this
                // level, safely. So, unset all forms of metadata on the object
                // and overwrite with out minimal version
                unset($d['latest_metadata']);
                unset($d['latestMetadata']);
                
                $d['latest_metadata'] = $miniMetadata;
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Team Dataset get all by status',
            ]);

            if ($metadataMissing) {
                // force reindex of collection
                $datasets->setCollection(
                    $datasets->getCollection()->values()
                );
            }

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
     *    path="/api/v2/teams/{teamId}/datasets/count/{field}",
     *    operationId="count_team_unique_fields_datasets_v2",
     *    tags={"Datasets"},
     *    summary="TeamDatasetController@count",
     *    description="Get team counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
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
    public function count(Request $request, int $teamId, string $field): JsonResponse
    {
        $this->checkAccess($request->all(), $teamId, null, 'team', $request->header());

        try {
            $counts = Dataset::where('team_id', $teamId)->applyCount();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Team Dataset count',
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
     *    path="/api/v2/teams/{teamId}/datasets/{id}",
     *    operationId="fetch_team_datasets_v2",
     *    tags={"Datasets"},
     *    summary="TeamDatasetController@show",
     *    description="Get dataset by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
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
    public function show(GetDataset $request, int $teamId, int $id): JsonResponse|BinaryFileResponse
    {
        try {
            $exportStructuralMetadata = $request->query('export', null);

            // Retrieve the dataset with collections, publications, and counts
            $dataset = Dataset::with("team")->whereRelation("team", "id", $teamId)->find($id);

            if (!$dataset) {
                return response()->json(['message' => 'Dataset not found'], 404);
            }

            list($dataset, $response) = $this->getDatasetDetails($dataset, $request);

            if ($response) {
                return $response;
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
                    'description' => 'Team Dataset get ' . $id . ' download structural metadata',
                ]);

                return Excel::download(new DatasetStructuralMetadataExport($export), 'dataset-structural-metadata.csv');
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Team Dataset get ' . $id,
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
     *    path="/api/v2/teams/{teamId}/datasets",
     *    operationId="create_team_datasets_v2",
     *    tags={"Datasets"},
     *    summary="TeamDatasetController@store",
     *    description="Create a new dataset for a team",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="create_origin", type="string", example="MANUAL"),
     *             @OA\Property(property="mongo_object_id", type="string", example="abc123"),
     *             @OA\Property(property="mongo_id", type="string", example="456"),
     *             @OA\Property(property="mongo_pid", type="string", example="def789"),
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
    public function store(CreateTeamDataset $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        list($userId) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $this->checkAccess($input, $teamId, null, 'team', $request->header());

        try {
            $elasticIndexing = $request->boolean('elastic_indexing', false);

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

            $input['team_id'] = $teamId;
            $input['user_id'] = $jwtUser['id'] ?? $userId;

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
                    'team_id' => $team['id'],
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Team Dataset ' . $metadataResult['dataset_id'] . ' with version ' .
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
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v2/teams/{teamId}/datasets/{id}",
     *    operationId="update_team_datasets_v2",
     *    tags={"Datasets"},
     *    summary="TeamDatasetController@update",
     *    description="Update a team dataset with a new dataset version",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
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
    public function update(UpdateTeamDataset $request, int $teamId, int $id)
    {
        $input = $request->all();
        list($userId, $appTeamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team', $request->header());

        try {
            $elasticIndexing = $request->boolean('elastic_indexing', false);
            $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ? $input['is_cohort_discovery'] : false;

            $input['team_id'] = $teamId;

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
                'user_id' => $jwtUser['id'] ?? $userId,
                'team_id' => $teamId,
                'updated' => $updateTime,
                'pid' => $currentPid,
                'create_origin' => $createOrigin,
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
            if ($request['status'] === Dataset::STATUS_ACTIVE) {

                LinkageExtraction::dispatch(
                    $currDataset->id,
                    $datasetVersionId,
                );
                if (Config::get('ted.enabled')) {
                    $tedData = Config::get('ted.use_partial') ? $input['metadata']['metadata']['summary'] : $input['metadata']['metadata'];

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
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
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

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v2/teams/{teamId}/datasets/{id}",
     *    operationId="patch_team_datasets_v2",
     *    tags={"Datasets"},
     *    summary="TeamDatasetController@edit",
     *    description="Edit a dataset owned by a team",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
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
     *             @OA\Property(property="create_origin", type="string", example="MANUAL"),
     *             @OA\Property(property="metadata", type="array", @OA\Items())
     *          )
     *       )
     *    ),
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
    public function edit(EditTeamDataset $request, int $teamId, int $id)
    {
        $input = $request->all();
        list($userId) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team', $request->header());

        try {
            //TODO: how to edit correctly, particularly the metadata? Assume if it's provided then it overwrites, otherwise leave as it is?
            $datasetModel = Dataset::where('id', $id)->first();

            $datasetModel->status = $request['status'];
            $datasetModel->save();

            $metadata = DatasetVersion::where('dataset_id', $id)->latest()->first();

            if ($request['status'] === Dataset::STATUS_ACTIVE) {
                LinkageExtraction::dispatch(
                    $datasetModel->id,
                    $metadata->id,
                );
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

            return response()->json([
                'message' => 'success'
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/teams/{teamId}/datasets/{id}",
     *      operationId="delete_team_datasets_v2",
     *      summary="TeamDatasetController@destroy",
     *      description="Delete a team's dataset",
     *      tags={"Datasets"},
     *      summary="TeamDatasetController@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="teamId",
     *         in="path",
     *         description="team id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="team id",
     *         ),
     *      ),
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
    public function destroy(DeleteDataset $request, int $teamId, int $id) // softdelete
    {
        $input = $request->all();
        list($userId) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team', $request->header());

        try {
            $dataset = Dataset::where(['id' => $id, 'team_id' => $teamId])->first();
            if (!$dataset) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_UNAUTHORIZED.message'),
                ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
            }

            $deleteFromElastic = ($dataset->status === Dataset::STATUS_ACTIVE);

            MMC::deleteDataset($id);

            Auditor::log([
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Dataset ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }
}
