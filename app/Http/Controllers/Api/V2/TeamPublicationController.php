<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Publication;
use App\Http\Traits\CheckAccess;
use App\Models\DurHasPublication;
use Illuminate\Http\JsonResponse;
use App\Models\PublicationHasTool;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Models\CollectionHasPublication;
use App\Exceptions\UnauthorizedException;
use App\Http\Traits\PublicationsV2Helper;
use App\Http\Traits\RequestTransformation;
use App\Models\PublicationHasDatasetVersion;
use App\Http\Requests\V2\Publication\CreatePublicationByTeamId;
use App\Http\Requests\V2\Publication\GetPublicationByTeamAndId;
use App\Http\Requests\V2\Publication\EditPublicationByTeamIdById;
use App\Http\Requests\V2\Publication\DeletePublicationByTeamIdById;
use App\Http\Requests\V2\Publication\GetPublicationByTeamAndStatus;
use App\Http\Requests\V2\Publication\UpdatePublicationByTeamIdById;
use App\Http\Requests\V2\Publication\GetPublicationCountByTeamAndStatus;

class TeamPublicationController extends Controller
{
    use RequestTransformation;
    use CheckAccess;
    use PublicationsV2Helper;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/v2/teams/{teamId}/publications/{status}",
     *     operationId="fetch_all_publications_by_team_and_status_v2",
     *     tags={"Publication"},
     *     summary="TeamPublicationController@indexStatus",
     *     description="Returns a list of a teams publications",
     *     @OA\Parameter(
     *         name="teamId",
     *         in="path",
     *         description="ID of the team",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="path",
     *         description="Status of the team (active, draft, or archived). Defaults to active if not provided.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"active", "draft", "archived"},
     *             default="active"
     *         )
     *     ),
     *    @OA\Parameter(
     *       name="paper_title",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter Publication by title"
     *    ),
     *     @OA\Response(
     *        response="200",
     *        description="Success response",
     *        @OA\JsonContent(
     *           @OA\Property(
     *              property="data",
     *              type="array",
     *              example="[]",
     *              @OA\Items(
     *                 type="array",
     *                 @OA\Items()
     *              )
     *           ),
     *        ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     *
     * @param  GetPublicationByTeamAndStatus  $request
     * @param  int  $teamId
     * @param  string|null  $status
     * @return JsonResponse
     */
    public function indexStatus(GetPublicationByTeamAndStatus $request, int $teamId, ?string $status = 'active'): JsonResponse
    {
        $this->checkAccess($request->all(), $teamId, null, 'team');

        try {
            $paperTitle = $request->query('paper_title', null);
            $perPage = request('per_page', Config::get('constants.per_page'));

            $publications = Publication::where([
                    'team_id' => $teamId,
                    'status' => strtoupper($status),
                ])
                ->when($paperTitle, function ($query) use ($paperTitle) {
                    return $query->where('paper_title', 'like', '%'. $paperTitle .'%');
                })
                ->with(['tools'])
                ->applySorting()
                ->paginate($perPage, ['*'], 'page');

            $publications->getCollection()->transform(function ($publication) {
                $publication->setAttribute('datasets', $publication->allDatasets);
                return $publication;
            });

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Publication get all by status',
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
     *    path="/api/v2/teams/{teamId}/publications/count/{field}",
     *    operationId="count_team_unique_fields_publication_v2",
     *    tags={"Publication"},
     *    summary="TeamPublicationController@count",
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
    public function count(GetPublicationCountByTeamAndStatus $request, int $teamId, string $field): JsonResponse
    {
        $this->checkAccess($request->all(), $teamId, null, 'team');

        try {
            $counts = Publication::where('team_id', $teamId)->applyCount();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Team Publication count',
            ]);

            return response()->json([
                'data' => $counts
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
     *    path="/api/v2/teams/{teamId}/publications/{id}",
     *    operationId="fetch_publications_by_team_and_by_id_v2",
     *    tags={"Publication"},
     *    summary="TeamPublicationController@show",
     *    description="Get publication by team id and by id",
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
     * @param  GetPublicationByTeamAndId  $request
     * @param  int  $teamId
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(GetPublicationByTeamAndId $request, int $teamId, int $id): JsonResponse
    {
        $this->checkAccess($request->all(), $teamId, null, 'team');

        try {
            $publication = Publication::where([
                                'team_id' => $teamId,
                                'id' => $id,
                            ])
                            ->with(['tools', 'durs', 'collections'])
                            ->first();
            if (!$publication) {
                throw new NotFoundException();
            }
            $publication->setAttribute('datasets', $publication->allDatasets);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Publication get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $publication,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (NotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
     *    path="/api/v2/teams/{teamId}/publications",
     *    operationId="create_publications_v2_by_team_id",
     *    tags={"Publication"},
     *    summary="TeamPublicationController@store",
     *    description="Create a new publication by team id",
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
     *
     * @param  CreatePublicationByTeamId  $request
     * @param  int  $teamId
     * @return JsonResponse
     */
    public function store(CreatePublicationByTeamId $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        $this->checkAccess($input, $teamId, null, 'team');

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
                'team_id' => $teamId,
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
                'description' => 'Team Publication ' . $publicationId . ' created',
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
     *    path="/api/v2/teams/{teamId}/publications/{id}",
     *    operationId="update_publications_v2_by_team_id",
     *    tags={"Publication"},
     *    summary="TeamPublicationController@update",
     *    description="Update publications by team id",
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
     *
     * @param  UpdatePublicationByTeamIdById  $request
     * @param  int  $teamId
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(UpdatePublicationByTeamIdById $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initPublication = Publication::where('id', $id)->first();
        if (!$initPublication) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, $teamId, null, 'team');
        if ($initPublication->team_id !== $teamId) {
            throw new UnauthorizedException();
        }
        try {
            Publication::where('id', $id)->first()->update([
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
                'team_id' => $teamId,
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
                'description' => 'Team Publication ' . $id . ' updated',
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
     *    path="/api/v2/teams/{teamId}/publications/{id}",
     *    operationId="edit_publications_v2_by_team_id",
     *    tags={"Publication"},
     *    summary="TeamPublicationController@edit",
     *    description="Edit publications by team id",
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
     *
     * @param  EditPublicationByTeamIdById  $request
     * @param  int  $teamId
     * @param  int  $id
     * @return JsonResponse
     */
    public function edit(EditPublicationByTeamIdById $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initPublication = Publication::where('id', $id)->first();
        if (!$initPublication) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, $initPublication->team_id, null, 'team');
        if ($initPublication->team_id !== $teamId) {
            throw new UnauthorizedException();
        }
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
            $arrayKeys['team_id'] = $teamId;

            Publication::where('id', $id)->first()->update($array);

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
     *    path="/api/v2/teams/{teamId}/publications/{id}",
     *    operationId="delete_publications_v2_by_team_id",
     *    tags={"Publication"},
     *    summary="TeamPublicationController@destroy",
     *    description="Delete publication by team id and id",
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
     * @param  DeletePublicationByTeamIdById  $request
     * @param  int  $teamId
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(DeletePublicationByTeamIdById $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $publication = Publication::where('id', $id)->first();
        if (!$publication) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, $teamId, null, 'team');
        if ($publication->team_id !== $teamId) {
            throw new UnauthorizedException();
        }

        try {
            PublicationHasDatasetVersion::where('publication_id', $id)->delete();
            PublicationHasTool::where(['publication_id' => $id])->delete();
            DurHasPublication::where(['publication_id' => $id])->delete();
            CollectionHasPublication::where(['publication_id' => $id])->delete();

            Publication::where(['id' => $id])->first()->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Publication ' . $id . ' deleted',
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
