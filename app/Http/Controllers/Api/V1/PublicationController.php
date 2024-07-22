<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use MetadataManagementController AS MMC;

use App\Exceptions\NotFoundException;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Http\Requests\Publication\GetPublication;
use App\Http\Requests\Publication\EditPublication;
use App\Http\Requests\Publication\CreatePublication;
use App\Http\Requests\Publication\DeletePublication;
use App\Http\Requests\Publication\UpdatePublication;

use App\Http\Traits\RequestTransformation;
use App\Models\PublicationHasTool;

class PublicationController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *    path="/api/v1/publications",
     *    operationId="fetch_all_publications",
     *    tags={"Publication"},
     *    summary="PublicationController@index",
     *    description="Get All Publications",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="paper_title",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter tools by paper title"
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
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $mongoId = $request->query('mongo_id', null);
            $paperTitle = $request->query('paper_title', null);
            $perPage = request('per_page', Config::get('constants.per_page'));
            $withRelated = $request->boolean('with_related', true);

            $publications = Publication::when($paperTitle, function ($query) use ($paperTitle) {
                return $query->where('paper_title', 'LIKE', '%' . $paperTitle . '%');
            })
            ->when($mongoId, function ($query) use ($mongoId) {
                return $query->where('mongo_id', '=', $mongoId);
            })
            ->when($withRelated, fn($query) => $query->with(['tools']))
            ->paginate($perPage, ['*'], 'page');

            // Ensure datasets are loaded via the accessor
            if ($withRelated) {
                $publications->getCollection()->transform(function ($publication) {
                    $publication->setAttribute('datasets', $publication->getLatestDatasets());
                    return $publication;
                });
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Publication get all",
            ]);

            return response()->json(
                $publications
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/publications/{id}",
     *    operationId="fetch_publications",
     *    tags={"Publication"},
     *    summary="PublicationController@show",
     *    description="Get publication by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="publication id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="publication id",
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
    public function show(GetPublication $request, int $id): JsonResponse
    {
        try {
            $publication = $this->getPublicationById($id);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Publication get " . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $publication,
            ], Config::get('statuscodes.STATUS_OK.code'));
    } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/publications",
     *    operationId="create_publications",
     *    tags={"Publication"},
     *    summary="PublicationController@store",
     *    description="Create a new publication",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="paper_title", type="string", example="A title"),
     *             @OA\Property(property="authors", type="string", example="Author A., Author B."),
     *             @OA\Property(property="year_of_publication", type="string", example="2024"),
     *             @OA\Property(property="paper_doi", type="string", example="10.12345"),
     *             @OA\Property(property="publication_type", type="string", example="Journal article, Book"),
     *             @OA\Property(property="journal_name", type="string", example="A Journal"),
     *             @OA\Property(property="abstract", type="string", example="A long description of the paper"),
     *             @OA\Property(property="url", type="string", example="http://example"),
     *             @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *             @OA\Property(property="datasets", type="array", 
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer"),
     *                   @OA\Property(property="link_type", type="string"),
     *                )
     *             ),
     *             @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=201,
     *        description="Created",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="success"),
     *            @OA\Property(property="data", type="integer", example="100")
     *        )
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     */
    public function store(CreatePublication $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $publication = Publication::create([
                'paper_title' => $input['paper_title'],
                'authors' => $input['authors'],
                'year_of_publication' => $input['year_of_publication'],
                'paper_doi' => $input['paper_doi'],
                'publication_type' => array_key_exists('publication_type', $input) ? $input['publication_type'] : '',
                'publication_type_mk1' => array_key_exists('publication_type_mk1', $input) ? $input['publication_type_mk1'] : '',
                'journal_name' => $input['journal_name'],
                'abstract' => $input['abstract'],
                'url' => $input['url'],
                'mongo_id' => array_key_exists('mongo_id', $input) ? $input['mongo_id'] : null,
            ]);
            $publicationId = (int) $publication->id;

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($publicationId, $datasets);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($publicationId, $tools, (int) $jwtUser['id']);

            $this->indexElasticPublication($publicationId);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Publication " . $publication->id . " created",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $publication->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/publications/{id}",
     *    operationId="update_publications",
     *    tags={"Publication"},
     *    summary="PublicationController@update",
     *    description="Update publications",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="publication id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="publications id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="paper_title", type="string", example="A title"),
     *             @OA\Property(property="authors", type="string", example="Author A., Author B."),
     *             @OA\Property(property="year_of_publication", type="string", example="2024"),
     *             @OA\Property(property="paper_doi", type="string", example="10.12345"),
     *             @OA\Property(property="publication_type", type="string", example="Journal article, Book"),
     *             @OA\Property(property="journal_name", type="string", example="A Journal"),
     *             @OA\Property(property="abstract", type="string", example="A long description of the paper"),
     *             @OA\Property(property="url", type="string", example="http://example"),
     *             @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *             @OA\Property(property="datasets", type="array", 
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer"),
     *                   @OA\Property(property="link_type", type="string"),
     *                )
     *             ),
     *             @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message", type="string", example="success"),
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
     *    @OA\Response(
     *        response=400,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="bad request"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     */
    public function update(UpdatePublication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            Publication::where('id', $id)->update([
                'paper_title' => $input['paper_title'],
                'authors' => $input['authors'],
                'year_of_publication' => $input['year_of_publication'],
                'paper_doi' => $input['paper_doi'],
                'publication_type' => array_key_exists('publication_type', $input) ? $input['publication_type'] : '',
                'publication_type_mk1' => array_key_exists('publication_type_mk1', $input) ? $input['publication_type_mk1'] : '',
                'journal_name' => $input['journal_name'],
                'abstract' => $input['abstract'],
                'url' => $input['url'],
                'mongo_id' => array_key_exists('mongo_id', $input) ? $input['mongo_id'] : null,
            ]);

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($id, $datasets);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($id, $tools, (int) $jwtUser['id']);

            $this->indexElasticPublication((int) $id);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Publication " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getPublicationById($id),
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

   /**
     * @OA\Patch(
     *    path="/api/v1/publications/{id}",
     *    operationId="edit_publications",
     *    tags={"Publication"},
     *    summary="PublicationController@edit",
     *    description="Edit publications",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="publications id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="publications id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="paper_title", type="string", example="A title"),
     *             @OA\Property(property="authors", type="string", example="Author A., Author B."),
     *             @OA\Property(property="year_of_publication", type="string", example="2024"),
     *             @OA\Property(property="paper_doi", type="string", example="10.12345"),
     *             @OA\Property(property="publication_type", type="string", example="Journal article, Book"),
     *             @OA\Property(property="journal_name", type="string", example="A Journal"),
     *             @OA\Property(property="abstract", type="string", example="A long description of the paper"),
     *             @OA\Property(property="url", type="string", example="http://example"),
     *             @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *             @OA\Property(property="datasets", type="array", 
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer"),
     *                   @OA\Property(property="link_type", type="string"),
     *                )
     *             ),
     *             @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message", type="string", example="success"),
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
     *    @OA\Response(
     *        response=400,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="bad request"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     */
    public function edit(EditPublication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $arrayKeys = [
                'paper_title',
                'authors',
                'year_of_publication',
                'paper_doi',
                'publication_type',
                'publication_type_mk1',
                'journal_name',
                'abstract',
                'url',
                'mongo_id',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            Publication::where('id', $id)->update($array);

            if (array_key_exists('datasets', $input)) {
                $datasets = $input['datasets'];
                $this->checkDatasets($id, $datasets);
            }

            if (array_key_exists('tools', $input)) {
                $tools = $input['tools'];
                $this->checkTools($id, $tools, $jwtUser['id']);
            }

            $this->indexElasticPublication((int) $id);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Publication " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getPublicationById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/publications/{id}",
     *    operationId="delete_publications",
     *    tags={"Publication"},
     *    summary="PublicationController@destroy",
     *    description="Delete publication by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="publication id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="publication id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     *
     */
    public function destroy(DeletePublication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $publication = Publication::findOrFail($id);
            if ($publication) {
                PublicationHasDatasetVersion::where('publication_id', $id)->delete();
                PublicationHasTool::where(['publication_id' => $id])->delete();
                $publication->delete();

                Auditor::log([
                    'user_id' => (int) $jwtUser['id'],
                    'action_type' => 'DELETE',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Publication " . $id . " deleted",
                ]);
    
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Calls a re-indexing of Elastic search when a publication is created or updated
     * 
     * @param string $id The publication id from the DB
     * 
     * @return void
     */
    public function indexElasticPublication(string $id): void
    {
        try {
            $pubMatch = Publication::where(['id' => $id])->first();
            if (!$pubMatch) {
                throw new Exception("Publication not found!");
            }
            $datasets = $pubMatch->getLatestDatasets();
            $datasetTitles = [];
            $datasetLinkTypes = [];
            foreach ($datasets as $dataset) {
                $datasetId = $dataset->id;
                $metadata = Dataset::where(['id' => $datasetId])
                    ->first()
                    ->latestVersion()
                    ->metadata;
                $latestVersionID = $dataset -> latestVersion()->id;
                $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
                $linkType = PublicationHasDatasetVersion::where([
                    ['publication_id', '=', (int) $id],
                    ['dataset_version_id', '=', (int) $latestVersionID]
                ])->first()->link_type ?? 'UNKNOWN';
                $datasetLinkTypes[] = $linkType;
            }

            // Split string to array of strings
            $publicationTypes = explode(",", $pubMatch['publication_type']); 

            $toIndex = [
                'title' => $pubMatch['paper_title'],
                'journalName' => $pubMatch['journal_name'],
                'abstract' => $pubMatch['abstract'],
                'authors' => $pubMatch['authors'],
                'publicationDate' => $pubMatch['year_of_publication'],
                'datasetTitles' => $datasetTitles,
                'publicationType' => $publicationTypes,
                'datasetLinkTypes' => $datasetLinkTypes,
            ];
            $params = [
                'index' => 'publication',
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];
            
            $client = MMC::getElasticClient();
            $client->index($params);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    private function getPublicationById(int $publicationId)
    {
        $publication = Publication::with([
            'tools', 
        ])->where([
            'id' => $publicationId
        ])->first();

        $publication->setAttribute('datasets', $publication->getLatestDatasets());

        return $publication;
    }

    // datasets
    private function checkDatasets(int $publicationId, array $inDatasets) 
    {
        $pubs = PublicationHasDatasetVersion::where(['publication_id' => $publicationId])->get();
        foreach ($pubs as $pub) {
            $dataset_id = DatasetVersion::where("id", $pub->dataset_version_id)->first()->dataset_id;
            if (!in_array($dataset_id, $this->extractInputIdToArray($inDatasets))) {
                $this->deletePublicationHasDatasetVersions($publicationId, $pub->dataset_version_id);
            }
        }

        foreach ($inDatasets as $dataset) {
            $datasetVersionId = Dataset::where('id', (int) $dataset['id'])->first()->latestVersion()->id;
            $checking = $this->checkInPublicationHasDatasetVersions($publicationId, $datasetVersionId);
        
            if (!$checking) {
                $this->addPublicationHasDatasetVersion($publicationId, $dataset, $datasetVersionId);
                //MMC::reindexElastic($dataset['id']);
            }
        }
    }

    private function addPublicationHasDatasetVersion(int $publicationId, array $dataset, int $datasetVersionId)
    {
        try {
            $datasetId = $dataset['id']; 
        
            if (!$datasetId) {
                throw new Exception("Dataset version not found for dataset ID: " . $datasetId);
            }

            $arrCreate = [
                'publication_id' => $publicationId,
                'dataset_version_id' => $datasetVersionId,
                'link_type' => $dataset['link_type'] ?? 'USING', // Assuming default link_type is 'USING'
            ];

            if (array_key_exists('updated_at', $dataset)) { // special for migration
                $arrCreate['created_at'] = $dataset['updated_at'];
                $arrCreate['updated_at'] = $dataset['updated_at'];
            }

            return PublicationHasDatasetVersion::updateOrCreate($arrCreate, ['link_type' => $arrCreate['link_type']]);
        } catch (Exception $e) {
            throw new Exception("addPublicationHasDatasetVersion :: " . $e->getMessage());
        }
    }

    private function checkInPublicationHasDatasetVersions(int $publicationId, int $datasetVersionId)
    {
        try {
            return PublicationHasDatasetVersion::where([
                'publication_id' => $publicationId,
                'dataset_version_id' => $datasetVersionId,
            ])->first();
        } catch (Exception $e) {
            throw new Exception("checkInPublicationHasDatasetVersions :: " . $e->getMessage());
        }
    }

    private function deletePublicationHasDatasetVersions(int $publicationId, int $datasetVersionId)
    {
        try {
            return PublicationHasDatasetVersion::where([
                'publication_id' => $publicationId,
                'dataset_version_id' => $datasetVersionId,
            ])->delete();
        } catch (Exception $e) {
            throw new Exception("deletePublicationHasDatasetVersions :: " . $e->getMessage());
        }
    }



    // tools
    private function checkTools(int $publicationId, array $inTools, int $userId = null) 
    {
        $pubs = PublicationHasTool::where(['publication_id' => $publicationId])->get();
        foreach ($pubs as $pub) {
            if (!in_array($pub->tool_id, $this->extractInputIdToArray($inTools))) {
                $this->deletePublicationHasTools($publicationId, $pub->tool_id);
            }
        }

        foreach ($inTools as $tool) {
            $checking = $this->checkInPublicationHasTools($publicationId, (int) $tool['id']);

            if (!$checking) {
                $this->addPublicationHasTool($publicationId, $tool, $userId);
            }
        }
    }

    private function addPublicationHasTool(int $publicationId, array $tool, int $userId = null)
    {
        try {
            $arrCreate = [
                'publication_id' => $publicationId,
                'tool_id' => $tool['id'],
            ];

            if (array_key_exists('user_id', $tool)) {
                $arrCreate['user_id'] = (int) $tool['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('updated_at', $tool)) { // special for migration
                $arrCreate['created_at'] = $tool['updated_at'];
                $arrCreate['updated_at'] = $tool['updated_at'];
            }

            return PublicationHasTool::updateOrCreate(
                $arrCreate,
                [
                    'publication_id' => $publicationId,
                    'tool_id' => $tool['id'],
                ]
            );
        } catch (Exception $e) {
            throw new Exception("addPublicationHasTool :: " . $e->getMessage());
        }
    }

    private function checkInPublicationHasTools(int $publicationId, int $toolId)
    {
        try {
            return PublicationHasTool::where([
                'publication_id' => $publicationId,
                'tool_id' => $toolId,
            ])->first();
        } catch (Exception $e) {
            throw new Exception("checkInPublicationHasTools :: " . $e->getMessage());
        }
    }

    private function deletePublicationHasTools(int $publicationId, int $toolId)
    {
        try {
            return PublicationHasTool::where([
                'publication_id' => $publicationId,
                'tool_id' => $toolId,
            ])->delete();
        } catch (Exception $e) {
            throw new Exception("deletePublicationHasTools :: " . $e->getMessage());
        }
    }

    private function extractInputIdToArray(array $input): Array
    {
        $response = [];
        foreach ($input as $value) {
            $response[] = $value['id'];
        }

        return $response;
    }
}