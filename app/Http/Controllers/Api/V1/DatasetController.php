<?php

namespace App\Http\Controllers\Api\V1;

use Mauro;
use Config;
use Exception;
use MetadataManagementController AS MMC;

use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Requests\Dataset\GetDataset;
use App\Http\Requests\Dataset\CreateDataset;

use App\Jobs\TechnicalObjectDataStore;
use App\Models\DatasetHasNamedEntities;

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
        if ($request->has('withTrashed')) {
            $datasets = Dataset::withTrashed()->paginate(Config::get('constants.per_page'), ['*'], 'page')->withQueryString();
        } else {
            $datasets = Dataset::paginate(Config::get('constants.per_page'), ['*'], 'page');
        }

        foreach ($datasets as $dataset) {
            if ($dataset->datasetid) {
                $mauroDatasetIdMetadata = Mauro::getDatasetByIdMetadata($dataset['datasetid']);
                $dataset['mauro'] = array_key_exists('items', $mauroDatasetIdMetadata) ? $mauroDatasetIdMetadata['items'] : [];
            } else {
                $dataset['mauro'] = [];
            }
        }

        return response()->json(
            $datasets
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
            
            return response()->json([
                'message' => 'success',
                'data' => $dataset,
            ], 200);

            throw new NotFoundException();
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

            // First validate the incoming schema to ensure it's in GWDM format
            // if not, attempt to translate prior to saving
            $validateDataModelType = MMC::validateDataModelType(
                json_encode($input['dataset']),
                env('GWDM'),
                env('GWDM_CURRENT_VERSION')
            );

            if ($validateDataModelType) {
                $mauro = MMC::createMauroDataModel($user, $team, $input);

                if (!empty($mauro)) {
                    $dataset = MMC::createDataset([
                        'datasetid' => (string) $mauro['DataModel']['responseJson']['id'],
                        'label' => $input['label'],
                        'short_description' => $input['short_description'],
                        'user_id' => $input['user_id'],
                        'team_id' => $input['team_id'],
                        'dataset' => json_encode($input['dataset']),
                        'created' => now(),
                        'updated' => now(),
                        'submitted' => now(),
                        'create_origin' => $input['create_origin'],
                    ]);

                    // Dispatch this potentially lengthy subset of data
                    // to a technical object data store job - API doesn't
                    // care if it exists or not. We leave that determination to
                    // the service itself.

                    TechnicalObjectDataStore::dispatch(
                        $mauro['DataModel']['responseJson']['id'],
                        base64_encode(gzcompress(gzencode(json_encode($input['dataset']['metadata'])), 6))
                    );
                    
                    return response()->json([
                        'message' => 'created',
                        'data' => $dataset->id,
                    ], 201);
                }
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
                    $mauro = MMC::createMauroDataModel($user, $team, $input);
                    if (!empty($mauro)) {
                        $dataset = MMC::createDataset([
                            'datasetid' => (string) $mauro['DataModel']['responseJson']['id'],
                            'label' => $input['label'],
                            'short_description' => $input['short_description'],
                            'user_id' => $input['user_id'],
                            'team_id' => $input['team_id'],
                            // The raw JSON response from Traser of the translated
                            // dataset
                            'dataset' => json_encode($response),
                            'created' => now(),
                            'updated' => now(),
                            'submitted' => now(),
                            'create_origin' => $input['create_origin'],
                        ]);

                        // Dispatch this potentially lengthy subset of data
                        // to a technical object data store job - API doesn't
                        // care if it exists or not. We leave that determination to
                        // the service itself.
                        TechnicalObjectDataStore::dispatch(
                            $mauro['DataModel']['responseJson']['id'],
                            base64_encode(gzcompress(gzencode(json_encode($response)), 6))
                        );

                        return response()->json([
                            'message' => 'created',
                            'data' => $dataset->id,
                        ], 201);
                    }
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

    public function update(Request $request, int $id)
    {
        //
    }

    public function edit(Request $request, int $id)
    {
        //
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
            $dataset = Dataset::where('id', (int) $id)->first()->toArray();

            Mauro::deleteDataModel($dataset['datasetid']);

            Dataset::where('id', (int) $id)->delete();

            MMC::deleteFromElastic($id);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
