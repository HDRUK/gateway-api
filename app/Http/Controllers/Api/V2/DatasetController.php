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
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\MetadataOnboard;
use App\Http\Traits\MetadataVersioning;
use App\Models\Traits\ModelHelpers;
use App\Models\DatasetVersionHasDatasetVersion;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use MetadataManagementController as MMC;
use App\Http\Requests\V2\Dataset\GetDataset;
use App\Http\Traits\DatasetsV2Helpers;
use App\Http\Traits\RequestTransformation;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Requests\V2\Dataset\EditDataset;
use App\Http\Requests\V2\Dataset\CreateDataset;
use App\Http\Requests\V2\Dataset\DeleteDataset;
use App\Http\Requests\V2\Dataset\UpdateDataset;
use App\Exports\DatasetStructuralMetadataExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatasetController extends Controller
{
    use MetadataVersioning;
    use GetValueByPossibleKeys;
    use MetadataOnboard;
    use CheckAccess;
    use ModelHelpers;
    use RequestTransformation;
    use DatasetsV2Helpers;

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

            // apply any initial filters to get initial datasets // TODO: remove this status field?
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
                'description' => 'Dataset index v2',
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
     *    path="/api/v2/datasets/{id}",
     *    operationId="fetch_datasets_v2",
     *    tags={"Datasets"},
     *    summary="DatasetController@showActive",
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
    public function showActive(GetDataset $request, int $id): JsonResponse|BinaryFileResponse
    {
        try {
            $exportStructuralMetadata = $request->query('export', null);

            // Retrieve the dataset with collections, publications, and counts
            $dataset = Dataset::with("team")->where("status", Dataset::STATUS_ACTIVE)->find($id);

            if (!$dataset) {
                return response()->json(['message' => 'Dataset not found'], 404);
            }
            $dataset = $this->getDatasetDetails($dataset, $request);
            $latestVersionId = $dataset->latestVersionID($id);

            if ($exportStructuralMetadata === 'structuralMetadata') {
                $arrayDataset = $dataset->toArray();
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

            $dataset->setAttribute('linkages', $this->getLinkages($latestVersionId));

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
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
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
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team', $request->header());

        try {
            $elasticIndexing = $request->boolean('elastic_indexing', false);
            $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ? $input['is_cohort_discovery'] : false;

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
                'user_id' => $userId,
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
                'user_id' => isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId,
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
    public function edit(EditDataset $request, int $id)
    {
        $input = $request->all();
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
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
                'user_id' => isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId,
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
                'user_id' => isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId,
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
    public function destroy(DeleteDataset $request, int $id) // softdelete
    {
        $input = $request->all();
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $initDataset->team_id, null, 'team', $request->header());

        try {
            $dataset = Dataset::where('id', $id)->first();
            $deleteFromElastic = ($dataset->status === Dataset::STATUS_ACTIVE);

            MMC::deleteDataset($id);

            Auditor::log([
                'user_id' => isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Dataset ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => isset($jwtUser['id']) ? (int)$jwtUser['id'] : $userId,
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
}
