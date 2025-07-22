<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use Carbon\Carbon;
use App\Models\Publication;
use App\Http\Traits\CheckAccess;
use App\Models\DurHasPublication;
use Illuminate\Http\JsonResponse;
use App\Models\PublicationHasTool;
use App\Http\Controllers\Controller;
use App\Models\CollectionHasPublication;
use App\Http\Traits\PublicationsV2Helper;
use App\Http\Traits\RequestTransformation;
use App\Models\PublicationHasDatasetVersion;
use App\Http\Requests\V2\Publication\CreatePublicationByUserId;
use App\Http\Requests\V2\Publication\GetPublicationByUserAndId;
use App\Http\Requests\V2\Publication\EditPublicationByUserIdById;
use App\Http\Requests\V2\Publication\DeletePublicationByUserIdById;
use App\Http\Requests\V2\Publication\GetPublicationByUserAndStatus;
use App\Http\Requests\V2\Publication\UpdatePublicationByUserIdById;
use App\Http\Requests\V2\Publication\GetPublicationCountByUserAndStatus;

class UserPublicationController extends Controller
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
     *     path="/api/v2/users/{userId}/publications/{status}",
     *     operationId="fetch_all_publications_by_user_and_status_v2",
     *     tags={"Publication"},
     *     summary="UserPublicationController@indexStatus",
     *     description="Returns a list of a users publications",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID of the user",
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
     * @param  GetPublicationByUserAndStatus  $request
     * @param  int  $userId
     * @param  string|null  $status
     * @return JsonResponse
     */
    public function indexStatus(GetPublicationByUserAndStatus $request, int $userId, ?string $status = 'active'): JsonResponse
    {
        $input = $request->all();
        $this->checkAccess($input, null, $userId, 'user');

        try {
            $paperTitle = $request->query('paper_title', null);
            $perPage = request('per_page', Config::get('constants.per_page'));

            $publications = Publication::where([
                    'owner_id' => $userId,
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
                'description' => 'User Publication get by status',
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
     *    path="/api/v2/users/{userId}/publications/count/{field}",
     *    operationId="count_user_unique_fields_publication_v2",
     *    tags={"Publication"},
     *    summary="UserPublicationController@count",
     *    description="Get user counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="user id",
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
    public function count(GetPublicationCountByUserAndStatus $request, int $userId, string $field): JsonResponse
    {
        $this->checkAccess($request->all(), null, $userId, 'user');

        try {
            $counts = Publication::where('owner_id', $userId)->applyCount();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'User Publication count',
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
     *    path="/api/v2/users/{userId}/publications/{id}",
     *    operationId="fetch_publications_by_user_and_by_id_v2",
     *    tags={"Publication"},
     *    summary="UserPublicationController@show",
     *    description="Get publication by user id and by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="ID of the user",
     *       required=true,
     *       @OA\Schema(
     *          type="integer",
     *          format="int64"
     *       )
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
     * @param  GetPublicationByUserAndId  $request
     * @param  int  $userId
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(GetPublicationByUserAndId $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $this->checkAccess($input, null, $userId, 'user');

        try {
            $publication = Publication::where([
                                'owner_id' => $userId,
                                'id' => $id,
                            ])
                            ->with(['tools', 'durs', 'collections'])
                            ->first();
            $publication->setAttribute('datasets', $publication->allDatasets);

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
     *    path="/api/v2/users/{userId}/publications",
     *    operationId="create_publications_v2_by_user_id",
     *    tags={"Publication"},
     *    summary="UserPublicationController@store",
     *    description="Create a new publication by user id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="ID of the user",
     *       required=true,
     *       @OA\Schema(
     *          type="integer",
     *          format="int64"
     *       )
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
     * @param  CreatePublicationByUserId  $request
     * @param  int  $userId
     * @return JsonResponse
     */
    public function store(CreatePublicationByUserId $request, int $userId): JsonResponse
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
                'owner_id' => $userId,
                'status' => $request['status'],
            ]);
            $publicationId = (int)$publication->id;

            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($publicationId, $datasets);

            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($publicationId, $tools, $userId);

            $durs = array_key_exists('durs', $input) ? $input['durs'] : [];
            $this->checkDurs($publicationId, $durs, $userId);

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
     *    path="/api/v2/users/{userId}/publications/{id}",
     *    operationId="update_publications_v2_by_user_id",
     *    tags={"Publication"},
     *    summary="UserPublicationController@update",
     *    description="Update publications by user id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="ID of the user",
     *       required=true,
     *       @OA\Schema(
     *          type="integer",
     *          format="int64"
     *       )
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
     * @param  UpdatePublicationByUserIdById  $request
     * @param  int  $userId
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(UpdatePublicationByUserIdById $request, int $userId, int $id): JsonResponse
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
            $this->checkTools($id, $tools, $userId);

            $durs = array_key_exists('durs', $input) ? $input['durs'] : [];
            $this->checkDurs($id, $durs, $userId);

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
     *    path="/api/v2/users/{userId}/publications/{id}",
     *    operationId="edit_publications_v2_by_user_id",
     *    tags={"Publication"},
     *    summary="UserPublicationController@edit",
     *    description="Edit publications by user id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="ID of the user",
     *       required=true,
     *       @OA\Schema(
     *          type="integer",
     *          format="int64"
     *       )
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
     * @param  EditPublicationByUserIdById  $request
     * @param  int  $userId
     * @param  int  $id
     * @return JsonResponse
     */
    public function edit(EditPublicationByUserIdById $request, int $userId, int $id): JsonResponse
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
                $this->checkTools($id, $tools, $userId);
            }

            if (array_key_exists('durs', $input)) {
                $durs = $input['durs'];
                $this->checkDurs($id, $durs, $userId);
            }

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
     *    path="/api/v2/users/{userId}/publications/{id}",
     *    operationId="delete_publications_v2_by_user_id",
     *    tags={"Publication"},
     *    summary="UserPublicationController@destroy",
     *    description="Delete publication by user id and id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="userId",
     *       in="path",
     *       description="ID of the user",
     *       required=true,
     *       @OA\Schema(
     *          type="integer",
     *          format="int64"
     *       )
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
     * @param  DeletePublicationByUserIdById  $request
     * @param  int  $userId
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(DeletePublicationByUserIdById $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $publication = Publication::where(['id' => $id, 'owner_id' => $userId])->first();
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
