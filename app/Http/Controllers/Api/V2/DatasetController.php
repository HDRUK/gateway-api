<?php

namespace App\Http\Controllers\Api\V2;

use Auditor;
use Config;
use Exception;
use App\Models\Dataset;
use App\Models\Team;
use App\Context\PartnerContext;
use App\Services\DatasetService;
use App\Http\Traits\CheckAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\V2\Dataset\GetDataset;
use App\Http\Requests\V2\Dataset\EditDataset;
use App\Http\Requests\V2\Dataset\CreateDataset;
use App\Http\Requests\V2\Dataset\DeleteDataset;
use App\Http\Requests\V2\Dataset\UpdateDataset;
use App\Exports\DatasetStructuralMetadataExport;
use App\Http\Traits\GetValueByPossibleKeys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatasetController extends Controller
{
    use CheckAccess;
    use GetValueByPossibleKeys;

    public function __construct(
        private readonly DatasetService $datasetService,
        private readonly PartnerContext $partnerContext,
    ) {
    }

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
     *       @OA\Schema(type="string"),
     *    ),
     *    @OA\Parameter(
     *       name="title",
     *       in="query",
     *       description="Three or more characters to filter dataset titles by",
     *       example="hdr",
     *       @OA\Schema(type="string"),
     *    ),
     *    @OA\Parameter(
     *       name="status",
     *       in="query",
     *       description="Dataset status to filter by ('ACTIVE', 'DRAFT', 'ARCHIVED')",
     *       example="ACTIVE",
     *       @OA\Schema(type="string"),
     *    ),
     *    @OA\Parameter(
     *       name="with_metadata",
     *       in="query",
     *       description="Boolean whether to return dataset metadata",
     *       example="true",
     *       @OA\Schema(type="string"),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="data", type="array", example="[]", @OA\Items(type="array", @OA\Items()))
     *       )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $datasets = $this->datasetService->list(
                filterStatus: $request->query('status'),
                filterTitle: $request->query('title'),
                withMetadata: $request->boolean('with_metadata', true),
                perPage: $request->integer('per_page', Config::get('constants.per_page')),
            );

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset index v2',
            ]);

            // Transform each item through the partner resource while preserving
            // the flat Laravel paginator envelope (current_page, data, from, etc.)
            // that callers depend on. ResourceCollection changes the envelope to
            // { data, links, meta } which would be a breaking change.
            $resourceClass = $this->partnerContext->indexResourceFor(Dataset::class);
            $datasets->through(fn ($dataset) => $resourceClass::make($dataset)->resolve(request()));
            return response()->json($datasets);

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
     *       @OA\Schema(type="integer"),
     *    ),
     *    @OA\Parameter(
     *       name="export",
     *       in="query",
     *       description="Set to 'structuralMetadata' to download as CSV.",
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
     *          @OA\Property(property="data", type="array", example="[]", @OA\Items(type="array", @OA\Items()))
     *       )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="unauthorized"))
     *    ),
     *    @OA\Response(response=404, description="Not found response",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="not found"))
     *    )
     * )
     */
    public function showActive(GetDataset $request, int $id): JsonResponse|BinaryFileResponse
    {
        try {
            $dataset = $this->datasetService->findActive($id);

            if (!$dataset) {
                return response()->json(['message' => 'Dataset not found'], 404);
            }

            if ($request->query('export') === 'structuralMetadata') {
                return $this->streamStructuralMetadataExport($dataset, $id);
            }

            $dataset = $this->datasetService->prepareForShow(
                $dataset,
                $request->query('schema_model'),
                $request->query('schema_version'),
            );

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset get ' . $id,
            ]);

            // additional() places message alongside data at the root, preserving
            // the { message, data } envelope that callers depend on.
            $resourceClass = $this->partnerContext->resourceFor(Dataset::class);
            return $resourceClass::make($dataset)
                ->additional(['message' => 'success'])
                ->response()
                ->setStatusCode(200);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'failed to translate', 'details' => $e->getMessage()], 400);
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
     *    @OA\Response(response=201, description="Created",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="integer", example="100")
     *       )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="unauthorized"))
     *    ),
     *    @OA\Response(response=500, description="Error",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="error"))
     *    )
     * )
     */
    public function store(CreateDataset $request): JsonResponse
    {
        $input = $request->all();
        list($userId, $teamId, $createOrigin, $status) = $this->getAccessorUserAndTeam($request);
        $jwtUser = $input['jwt_user'] ?? [];
        $this->checkAccess($input, $teamId, null, 'team', $request->header());

        try {
            $team = Team::where('id', $teamId)->first();

            $input['user_id']       = $userId;
            $input['team_id']       = $teamId;
            $input['create_origin'] = $createOrigin;
            $input['status']        = $status;

            if (empty($input['metadata']['metadata']['summary']['title'])) {
                return response()->json(['message' => 'Title is required to save a dataset'], 400);
            }

            $result = $this->datasetService->create(
                input: $input,
                team: $team,
                inputSchema: $request->query('input_schema'),
                inputVersion: $request->query('input_version'),
                elasticIndexing: $request->boolean('elastic_indexing', false),
            );

            if ($result['translated']) {
                Auditor::log([
                    'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                    'team_id'     => $teamId,
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Dataset ' . $result['dataset_id'] . ' with version ' . $result['version_id'] . ' created',
                ]);

                return response()->json([
                    'message' => 'created',
                    'data'    => $result['dataset_id'],
                    'version' => $result['version_id'],
                ], 201);
            }

            return response()->json([
                'message' => 'metadata is in an unknown format and cannot be processed',
                'details' => $result['response'],
            ], 400);

        } catch (Exception $e) {
            Auditor::log([
                'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
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
     *       name="id", in="path", description="dataset id", required=true, example="1",
     *       @OA\Schema(type="integer"),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
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
     *    @OA\Response(response=201, description="Created",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="integer", example="100")
     *       )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="unauthorized"))
     *    ),
     *    @OA\Response(response=500, description="Error",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="error"))
     *    )
     * )
     */
    public function update(UpdateDataset $request, int $id): JsonResponse
    {
        $input = $request->all();
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $jwtUser = $input['jwt_user'] ?? [];
        $dataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $dataset->team_id, null, 'team', $request->header());

        try {
            $team          = Team::where('id', $teamId)->first();
            $versionNumber = $this->datasetService->update(
                dataset: $dataset,
                input: $input,
                userId: $userId,
                teamId: $teamId,
                createOrigin: $createOrigin,
                elasticIndexing: $request->boolean('elastic_indexing', false),
                team: $team,
            );

            Auditor::log([
                'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'team_id'     => $teamId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' with version ' . $versionNumber . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
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
     *    path="/api/v2/datasets/{id}",
     *    operationId="patch_datasets_v2",
     *    tags={"Datasets"},
     *    summary="DatasetController@edit",
     *    description="Patch dataset by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id", in="path", description="dataset id", required=true, example="1",
     *       @OA\Schema(type="integer"),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="create_origin", type="string", example="MANUAL"),
     *             @OA\Property(property="metadata", type="array", @OA\Items())
     *          )
     *       )
     *    ),
     *    @OA\Response(response=200, description="Success",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="success"))
     *    ),
     *    @OA\Response(response=500, description="Error",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="error"))
     *    )
     * )
     */
    public function edit(EditDataset $request, int $id): JsonResponse
    {
        $input = $request->all();
        list($userId, $teamId) = $this->getAccessorUserAndTeam($request);
        $jwtUser = $input['jwt_user'] ?? [];
        $dataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $dataset->team_id, null, 'team', $request->header());

        try {
            $this->datasetService->patch($dataset, $request['status']);

            Auditor::log([
                'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'team_id'     => $teamId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' marked as ' . strtoupper($request['status']),
            ]);

            return response()->json(['message' => 'success'], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
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
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id", in="path", description="dataset id", required=true, example="1",
     *         @OA\Schema(type="integer"),
     *      ),
     *      @OA\Response(response=404, description="Not found response",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="not found"))
     *      ),
     *      @OA\Response(response=200, description="Success",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="success"))
     *      ),
     *      @OA\Response(response=500, description="Error",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="error"))
     *      )
     * )
     */
    public function destroy(DeleteDataset $request, int $id): JsonResponse
    {
        $input = $request->all();
        list($userId, $teamId) = $this->getAccessorUserAndTeam($request);
        $jwtUser = $input['jwt_user'] ?? [];
        $dataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $dataset->team_id, null, 'team', $request->header());

        try {
            $this->datasetService->delete($id);

            Auditor::log([
                'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Dataset ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    public function destroyByPid(Request $request, string $pid): JsonResponse
    {
        $dataset = Dataset::where('pid', $pid)->firstOrFail();
        return $this->destroy($request, $dataset->id);
    }

    public function updateByPid(UpdateDataset $request, string $pid): JsonResponse
    {
        $dataset = Dataset::where('pid', $pid)->firstOrFail();
        return $this->update($request, $dataset->id);
    }

    // no Swagger
    public function updateIsCohortDiscovery(GetDataset $request, int $id): JsonResponse
    {
        try {
            $input             = $request->all();
            $isCohortDiscovery = $input['is_cohort_discovery'] ?? null;

            if (is_null($isCohortDiscovery)) {
                throw new Exception('Payload is missing is_cohort_discovery');
            }

            $dataset = Dataset::where('id', $id)->firstOrFail();
            $this->datasetService->updateCohortDiscovery($dataset, $isCohortDiscovery);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
     *       name="type", in="query", required=true,
     *       @OA\Schema(type="string", enum={"template_dataset_structural_metadata", "dataset_metadata"}),
     *    ),
     *    @OA\Response(response=200, description="CSV file",
     *       @OA\MediaType(mediaType="text/csv", @OA\Schema(type="string"))
     *    ),
     *    @OA\Response(response=401, description="Unauthorized",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="unauthorized"))
     *    ),
     *    @OA\Response(response=404, description="File Not Found",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="file_not_found"))
     *    ),
     * )
     */
    public function exportMock(Request $request): mixed
    {
        try {
            $file = match (strtolower((string) $request->query('type'))) {
                'template_dataset_structural_metadata' => Config::get('mock_data.template_dataset_structural_metadata'),
                'dataset_metadata'                     => Config::get('mock_data.mock_dataset_metadata'),
                default                                => null,
            };

            if (!$file || !Storage::disk('mock')->exists($file)) {
                return response()->json(['error' => 'File not found.'], 404);
            }

            return Storage::disk('mock')
                ->download($file)
                ->setStatusCode(Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build and stream the structural metadata CSV export for a dataset.
     * This is an HTTP output concern so it stays in the controller.
     */
    private function streamStructuralMetadataExport(Dataset $dataset, int $id): BinaryFileResponse
    {
        $latestVersionId = $dataset->latestVersionID($id);
        $arrayDataset    = $dataset->load('versions')->toArray();
        $versions        = $this->getValueByPossibleKeys($arrayDataset, ['versions'], []);
        $count           = 0;

        foreach ($versions as $index => $version) {
            if ((int) $version['id'] === (int) $latestVersionId) {
                $count = $index;
                break;
            }
        }

        $export = count($versions)
            ? $this->getValueByPossibleKeys($arrayDataset, ['versions.' . $count . '.metadata.metadata.structuralMetadata'], [])
            : [];

        Auditor::log([
            'action_type' => 'GET',
            'action_name' => class_basename($this) . '@showActive',
            'description' => 'Dataset get ' . $id . ' download structural metadata',
        ]);

        return Excel::download(new DatasetStructuralMetadataExport($export), 'dataset-structural-metadata.csv');
    }
}
