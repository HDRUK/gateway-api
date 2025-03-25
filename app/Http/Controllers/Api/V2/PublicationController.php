<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Traits\CheckAccess;
use App\Models\DurHasPublication;
use Illuminate\Http\JsonResponse;
use App\Models\PublicationHasTool;
use App\Http\Controllers\Controller;
use App\Models\CollectionHasPublication;
use App\Http\Traits\PublicationsV2Helper;
use App\Http\Traits\RequestTransformation;
use App\Models\PublicationHasDatasetVersion;
use App\Http\Requests\Publication\GetPublication;
use App\Http\Requests\Publication\EditPublication;
use App\Http\Requests\Publication\CreatePublication;
use App\Http\Requests\Publication\DeletePublication;
use App\Http\Requests\Publication\UpdatePublication;

class PublicationController extends Controller
{
    use RequestTransformation;
    use CheckAccess;
    use PublicationsV2Helper;

    /**
     * @OA\Get(
     *    path="/api/v2/publications",
     *    operationId="fetch_all_publications_v2",
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
     *    @OA\Parameter(
     *       name="owner_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="int"),
     *       description="Filter tools by owner id"
     *    ),
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="int"),
     *       description="Filter tools by team id"
     *    ),
     *    @OA\Parameter(
     *       name="status",
     *       in="query",
     *       description="Publication status to filter by ('ACTIVE', 'DRAFT', 'ARCHIVED')",
     *       example="ACTIVE",
     *       @OA\Schema(
     *          type="string",
     *          description="Publication status to filter by",
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
            $ownerId = $request->query('owner_id', null);
            $teamId = $request->query('team_id', null);
            $filterStatus = $request->query('status', null);
            $perPage = request('per_page', Config::get('constants.per_page'));
            $withRelated = $request->boolean('with_related', true);

            $publications = Publication::when($paperTitle, function ($query) use ($paperTitle) {
                return $query->where('paper_title', 'LIKE', '%' . $paperTitle . '%');
            })
            ->when($mongoId, function ($query) use ($mongoId) {
                return $query->where('mongo_id', '=', $mongoId);
            })
            ->when($ownerId, function ($query) use ($ownerId) {
                return $query->where('owner_id', '=', $ownerId);
            })
            ->when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })
            ->when($filterStatus, function ($query) use ($filterStatus) {
                return $query->where('status', '=', $filterStatus);
            })
            ->when($withRelated, fn ($query) => $query->with(['tools']));
            if ($request->has('sort')) {
                $publications = $publications->applySorting();
            }
            $publications = $publications->paginate($perPage, ['*'], 'page');

            // Ensure datasets are loaded via the accessor
            if ($withRelated) {
                $publications->getCollection()->transform(function ($publication) {
                    $publication->setAttribute('datasets', $publication->allDatasets);
                    return $publication;
                });
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication get all',
            ]);

            return response()->json(
                $publications
            );
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
     *    path="/api/v2/publications/count/{field}",
     *    operationId="count_unique_fields_publications_v2",
     *    tags={"Publication"},
     *    summary="PublicationController@count",
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
     *       name="owner_id",
     *       in="query",
     *       description="owner id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="owner id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       required=false,
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
        try {
            $ownerId = $request->query('owner_id', null);
            $teamId = $request->query('team_id', null);
            $counts = Publication::when($ownerId, function ($query) use ($ownerId) {
                return $query->where('owner_id', '=', $ownerId);
            })->when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })
            ->select($field)
            ->get()
            ->groupBy($field)
            ->map
            ->count();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => "Publication count",
            ]);

            return response()->json([
                "data" => $counts
            ]);
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
     *    path="/api/v2/publications/{id}",
     *    operationId="fetch_publications_v2",
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
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $publication,
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
     * @OA\Post(
     *    path="/api/v2/publications",
     *    operationId="create_publications_v2",
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
     *                   @OA\Property(property="description", type="string"),
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $teamId = array_key_exists('team_id', $input) ? $input['team_id'] : null;
        if (!is_null($teamId)) {
            $this->checkAccess($input, $teamId, null, 'team');
        }

        try {
            $publication = Publication::create([
                'paper_title' => $input['paper_title'],
                'authors' => $input['authors'],
                'year_of_publication' => $input['year_of_publication'],
                'paper_doi' => $input['paper_doi'],
                'publication_type' => array_key_exists('publication_type', $input) ? $input['publication_type'] : '',
                'publication_type_mk1' => array_key_exists('publication_type_mk1', $input) ? $input['publication_type_mk1'] : '',
                'journal_name' => $input['journal_name'],
                'abstract' => $input['abstract'],
                'url' => array_key_exists('url', $input) ? $input['url'] : null,
                'mongo_id' => array_key_exists('mongo_id', $input) ? $input['mongo_id'] : null,
                'team_id' => array_key_exists('team_id', $input) ? $input['team_id'] : null,
                'owner_id' => (int)$jwtUser['id'],
                'status' => $request['status'],
            ]);
            $publicationId = (int)$publication->id;

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($publicationId, $datasets);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($publicationId, $tools, (int)$jwtUser['id']);

            $durs = array_key_exists('durs', $input) ? $input['durs'] : [];
            $this->checkDurs($publicationId, $durs, (int)$jwtUser['id']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication ' . $publication->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $publication->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v2/publications/{id}",
     *    operationId="update_publications_v2",
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
     *             @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
     *             @OA\Property(property="datasets", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer"),
     *                   @OA\Property(property="link_type", type="string"),
     *                   @OA\Property(property="description", type="string"),
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initPublication = Publication::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, null, $initPublication->owner_id, 'user');

        try {

            if ($initPublication['status'] === Publication::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current publication! Status already "ARCHIVED"');
            }

            Publication::where('id', $id)->update([
                'paper_title' => $input['paper_title'],
                'authors' => $input['authors'],
                'year_of_publication' => $input['year_of_publication'],
                'paper_doi' => $input['paper_doi'],
                'publication_type' => array_key_exists('publication_type', $input) ? $input['publication_type'] : '',
                'publication_type_mk1' => array_key_exists('publication_type_mk1', $input) ? $input['publication_type_mk1'] : '',
                'journal_name' => $input['journal_name'],
                'abstract' => $input['abstract'],
                'url' => array_key_exists('url', $input) ? $input['url'] : null,
                'mongo_id' => array_key_exists('mongo_id', $input) ? $input['mongo_id'] : null,
                'status' => array_key_exists('status', $input) ? $input['status'] : Publication::STATUS_DRAFT,
                'team_id' => array_key_exists('team_id', $input) ? $input['team_id'] : null,
            ]);

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($id, $datasets);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($id, $tools, (int)$jwtUser['id']);

            $durs = array_key_exists('durs', $input) ? $input['durs'] : [];
            $this->checkDurs($id, $durs, (int)$jwtUser['id']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getPublicationById($id),
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
      * @OA\Patch(
      *    path="/api/v2/publications/{id}",
      *    operationId="edit_publications_v2",
      *    tags={"Publication"},
      *    summary="PublicationController@edit",
      *    description="Edit publications",
      *    security={{"bearerAuth":{}}},
      *    @OA\Parameter(
      *       name="unarchive",
      *       in="query",
      *       description="Unarchive a publication",
      *       @OA\Schema(
      *          type="string",
      *          description="instruction to unarchive publication",
      *       ),
      *    ),
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
      *             @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
      *             @OA\Property(property="datasets", type="array",
      *                @OA\Items(type="object",
      *                   @OA\Property(property="id", type="integer"),
      *                   @OA\Property(property="link_type", type="string"),
      *                   @OA\Property(property="description", type="string"),
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $publicationModel = Publication::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, null, $publicationModel->owner_id, 'user');

        try {
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
                'status'
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            Publication::where('id', $id)->update($array);

            if (array_key_exists('datasets', $input)) {
                $datasets = $input['datasets'];
                $this->checkDatasets($id, $datasets);
            }

            if (array_key_exists('tools', $input)) {
                $tools = $input['tools'];
                $this->checkTools($id, $tools, $jwtUser['id'] ?? null);
            }

            $durs = array_key_exists('durs', $input) ? $input['durs'] : [];
            $this->checkDurs($id, $durs, (int)$jwtUser['id']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $this->getPublicationById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v2/publications/{id}",
     *    operationId="delete_publications_v2",
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $publication = Publication::where(['id' => $id])->first();
        $this->checkAccess($input, null, $publication->owner_id, 'user');

        try {
            PublicationHasDatasetVersion::where('publication_id', $id)->delete();
            PublicationHasTool::where(['publication_id' => $id])->delete();
            DurHasPublication::where(['publication_id' => $id])->delete();
            CollectionHasPublication::where(['publication_id' => $id])->delete();

            $publication->deleted_at = Carbon::now();
            $publication->status = Publication::STATUS_ARCHIVED;
            $publication->save();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication ' . $id . ' soft deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
