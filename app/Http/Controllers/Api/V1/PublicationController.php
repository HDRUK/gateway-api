<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use MetadataManagementController AS MMC;

use App\Exceptions\NotFoundException;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\PublicationHasDataset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Http\Requests\Publication\GetPublication;
use App\Http\Requests\Publication\EditPublication;
use App\Http\Requests\Publication\CreatePublication;
use App\Http\Requests\Publication\DeletePublication;
use App\Http\Requests\Publication\UpdatePublication;

use App\Http\Traits\RequestTransformation;

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
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $publications = Publication::paginate(Config::get('constants.per_page'), ['*'], 'page');

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
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
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $publication = Publication::where('id', $id)->get();
            if ($publication) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $publication,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Publication get " . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
     *             @OA\Property(property="datasets", type="array", 
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer"),
     *                   @OA\Property(property="link_type", type="string"),
     *                )
     *             ),
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
                'publication_type' => $input['publication_type'],
                'journal_name' => $input['journal_name'],
                'abstract' => $input['abstract'],
                'url' => $input['url'],
            ]);

            $datasetInput = array_key_exists('datasets', $input) ? $input['datasets']: [];
            if ($publication) {
                foreach ($datasetInput as $dataset) {
                    $linkType = array_key_exists('link_type,', $dataset) ? $dataset['link_type'] : 'UNKNOWN';
                    PublicationHasDataset::updateOrCreate([
                        'publication_id' => (int) $publication->id,
                        'dataset_id' => (int) $dataset['id'],
                        'link_type' => $linkType,
                    ]);
                }
                $this->indexElasticPublication($publication->id);
            } else {
                throw new NotFoundException();
            }

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
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
     *             @OA\Property(property="datasets", type="array", 
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer"),
     *                   @OA\Property(property="link_type", type="string"),
     *                )
     *             ),
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
                'publication_type' => $input['publication_type'],
                'journal_name' => $input['journal_name'],
                'abstract' => $input['abstract'],
                'url' => $input['url'],
            ]);

            $datasetInput = array_key_exists('datasets', $input) ? $input['datasets']: [];
            PublicationHasDataset::where('publication_id', $id)->delete();
            foreach ($datasetInput as $dataset) {
                $linkType = array_key_exists('link_type,', $dataset) ? $dataset['link_type'] : 'UNKNOWN';
                PublicationHasDataset::updateOrCreate([
                    'publication_id' => (int) $id,
                    'dataset_id' => (int) $dataset['id'],
                    'link_type' => $linkType,
                ]);
            }
            $this->indexElasticPublication((int) $id);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Publication " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Publication::where('id', $id)->get()[0],
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
     *             @OA\Property(property="datasets", type="array", 
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer"),
     *                   @OA\Property(property="link_type", type="string"),
     *                )
     *             ),
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
                'journal_name',
                'abstract',
                'url',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $publication = Publication::where('id', $id)->update($array);

            $datasetInput = array_key_exists('datasets', $input) ? $input['datasets']: [];
            PublicationHasDataset::where('publication_id', $id)->delete();
            foreach ($datasetInput as $dataset) {
                $linkType = array_key_exists('link_type,', $dataset) ? $dataset['link_type'] : 'UNKNOWN';
                PublicationHasDataset::updateOrCreate([
                    'publication_id' => (int) $id,
                    'dataset_id' => (int) $dataset['id'],
                    'link_type' => $linkType,
                ]);
            }
            $this->indexElasticPublication((int) $id);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Publication " . $id . " updated",
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
                PublicationHasDataset::where('publication_id', $id)->delete();
                $publication->delete();

                Auditor::log([
                    'user_id' => $jwtUser['id'],
                    'action_type' => 'DELETE',
                    'action_service' => class_basename($this) . '@'.__FUNCTION__,
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

            $pubMatch = Publication::where(['id' => $id])
                ->with('datasets')
                ->first()
                ->toArray();

            $datasetTitles = array();
            foreach ($pubMatch['datasets'] as $d) {
                $metadata = Dataset::where(['id' => $d])
                    ->first()
                    ->latestVersion()
                    ->metadata;
                $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
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
            ];

            $params = [
                'index' => 'publication',
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];
            
            $client = MMC::getElasticClient();
            $response = $client->index($params);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}