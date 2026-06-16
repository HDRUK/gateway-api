<?php

namespace App\Http\Controllers\Api\V1;

use DB;
use Config;
use Auditor;
use Exception;
use App\Models\Dur;
use App\Models\Role;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\Publication;
use App\Models\TeamHasUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\DatasetVersion;
use App\Models\TeamUserHasRole;
use App\Http\Traits\TrimPayload;
use App\Http\Traits\IndexElastic;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Team\GetTeam;
use App\Models\TeamHasNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\EditTeam;
use App\Exceptions\NotFoundException;
use App\Http\Requests\Team\CreateTeam;
use App\Http\Requests\Team\DeleteTeam;
use App\Http\Requests\Team\UpdateTeam;
use App\Http\Traits\TeamTransformation;
use App\Http\Traits\RequestTransformation;
use App\Http\Traits\GetValueByPossibleKeys;
use App\Http\Traits\CheckAccess;
use App\Models\TeamHasAlias;

class TeamController extends Controller
{
    use GetValueByPossibleKeys;
    use IndexElastic;
    use CheckAccess;
    use TeamTransformation;
    use RequestTransformation;
    use TrimPayload;

    private $datasets = [];
    private $durs = [];
    private $tools = [];
    private $publications = [];
    private $collections = [];
    private $aliases = [];
    private $associatedDatasetIds = [];

    /**
     * @OA\Get(
     *      path="/api/v1/teams",
     *      tags={"Teams"},
     *      summary="TeamController@index",
     *      description="Returns a list of teams enabled on the system",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *         response="200",
     *         description="Success response",
     *         @OA\JsonContent(
     *            @OA\Property(property="current_page", type="integer", example="1"),
     *               @OA\Property(property="data", type="array",
     *                  @OA\Items(type="object",
     *                    @OA\Property(property="id", type="integer", example="123"),
     *                    @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                    @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                    @OA\Property(property="enabled", type="boolean", example="1"),
     *                    @OA\Property(property="name", type="string", example="someName"),
     *                    @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                    @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                    @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                    @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                    @OA\Property(property="is_admin", type="boolean", example="1"),
     *                    @OA\Property(property="member_of", type="string", example="someOrg"),
     *                    @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                    @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                    @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *                    @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                    @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *                    @OA\Property(property="is_question_bank", type="boolean", example="1"),
     *                    @OA\Property(property="is_provider", type="boolean", example="1"),
     *                    @OA\Property(property="url", type="string", example="https://example/image.jpg"),
     *                    @OA\Property(property="introduction", type="string", example="info about the team"),
     *                    @OA\Property(property="service", type="string", example="https://example"),
     *                    @OA\Property(property="aliases", type="array", example="[]", @OA\Items()),
     *                ),
     *             ),
     *             @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams?page=1"),
     *             @OA\Property(property="from", type="integer", example="1"),
     *             @OA\Property(property="last_page", type="integer", example="1"),
     *             @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams?page=1"),
     *             @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *             @OA\Property(property="next_page_url", type="string", example="null"),
     *             @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams"),
     *             @OA\Property(property="per_page", type="integer", example="25"),
     *             @OA\Property(property="prev_page_url", type="string", example="null"),
     *             @OA\Property(property="to", type="integer", example="3"),
     *             @OA\Property(property="total", type="integer", example="3"),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $sort = [];
            $sortArray = $request->has('sort') ? explode(',', $request->query('sort', '')) : [];
            foreach ($sortArray as $item) {
                $tmp = explode(":", $item);
                $sort[$tmp[0]] = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';
            }

            $query = Team::where('enabled', 1);

            if ($request->has('uses_5_safes')) {
                $query->where('teams.uses_5_safes', $request->query('uses_5_safes'));
            }

            if ($request->has('is_question_bank')) {
                $query->where('teams.is_question_bank', $request->boolean('is_question_bank'));
            }

            foreach ($sort as $key => $value) {
                if ($key === 'created_at' || $key === 'updated_at') {
                    $query->orderBy('teams.' . $key, strtoupper($value));
                }

                if ($key === 'data_provider') {
                    $query->orderBy('teams.member_of', strtoupper($value));
                    $query->orderBy('teams.name', strtoupper($value));
                }
            }

            $perPage = request('per_page', Config::get('constants.per_page'));
            $teams = $query
                ->with(['users', 'aliases'])
                ->paginate($perPage, ['*'], 'page')
                ->toArray();

            $teams['data'] = $this->getTeams($teams['data']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Get all',
            ]);

            return response()->json(
                $teams
            );
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
     * @OA\Get(
     *      path="/api/v1/teams/names",
     *      tags={"Teams"},
     *      summary="TeamController@getNames",
     *      description="Returns a simple list of enabled teams (id and name only)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success response",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="name", type="string", example="Research Data Team")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function getNames(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        try {
            $query = Team::where('enabled', 1)
                ->select(['id', 'name']);
            if ($request->has('sort')) {
                $sortDirection = strtolower($request->query('sort')) === 'desc' ? 'desc' : 'asc';
                $query->orderBy('name', $sortDirection);
            } else {
                $query->orderBy('name', 'asc');
            }

            $teams = $query->get();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Get team names and IDs',
            ]);

            return response()->json(['data' => $teams], 200);

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}",
     *      tags={"Teams"},
     *      summary="TeamController@show",
     *      description="Return a single team",
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
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="name", type="string", example="someName"),
     *                  @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                  @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                  @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                  @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                  @OA\Property(property="is_admin", type="boolean", example="1"),
     *                  @OA\Property(property="member_of", type="string", example="someOrg"),
     *                  @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                  @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                  @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *                  @OA\Property(property="notifications", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *                  @OA\Property(property="is_question_bank", type="boolean", example="1"),
     *                  @OA\Property(property="is_provider", type="boolean", example="1"),
     *                  @OA\Property(property="url", type="string", example="https://example/image.jpg"),
     *                  @OA\Property(property="introduction", type="string", example="info about the team"),
     *                  @OA\Property(property="service", type="string", example="https://example"),
     *                  @OA\Property(property="aliases", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function show(GetTeam $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $userTeam = Team::where('id', $teamId)->with(['users', 'notifications', 'aliases'])->get()->toArray();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Get by ' . $teamId,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getTeams($userTeam),
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
    * @OA\Get(
    *      path="/api/v1/teams/{teamPid}/id",
    *      tags={"Teams"},
    *      summary="TeamController@getIdFromPid",
    *      description="Get the teamId from a Pid. Failure to find such a team results in a successful null response.",
    *      security={{"bearerAuth":{}}},
    *      @OA\Parameter(
    *         name="teamPid",
    *         in="path",
    *         description="team pid",
    *         required=true,
    *         example="c98b8ef9-f840-4823-b0b7-0d8575ce01e0",
    *         @OA\Schema(
    *            type="string",
    *            description="team pid",
    *         ),
    *      ),
    *      @OA\Response(
    *          response=200,
    *          description="Success",
    *          @OA\JsonContent(
    *              @OA\Property(property="message", type="string"),
    *              @OA\Property(property="data", type="number")
    *          ),
    *      )
    * )
    */
    public function getIdFromPid(Request $request, string $pid): JsonResponse
    {
        try {
            $id = Team::where('pid', $pid)->select('id')->firstOrFail()->id;
        } catch (Exception $e) {
            $id = null;
        }

        return response()->json([
            'message' => 'success',
            'data' => $id,
        ], 200);
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/search",
     *      summary="TeamController@searchByName",
     *      description="Return an array of teams matching search criteria",
     *      tags={"Teams"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Name to search for",
     *         required=true,
     *         @OA\Schema(
     *            type="string",
     *            description="Name to search for",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(type="object",
     *                    @OA\Property(property="id", type="integer", example="123"),
     *                    @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                    @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                    @OA\Property(property="enabled", type="boolean", example="1"),
     *                    @OA\Property(property="name", type="string", example="someName"),
     *                    @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                    @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                    @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                    @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                    @OA\Property(property="is_admin", type="boolean", example="1"),
     *                    @OA\Property(property="member_of", type="string", example="someOrg"),
     *                    @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                    @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                    @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *                    @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                    @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *                    @OA\Property(property="is_question_bank", type="boolean", example="1"),
     *                    @OA\Property(property="is_provider", type="boolean", example="1"),
     *                    @OA\Property(property="url", type="string", example="https://example/image.jpg"),
     *                    @OA\Property(property="introduction", type="string", example="info about the team"),
     *                    @OA\Property(property="service", type="string", example="https://example"),
     *                 ),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function searchByName(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $teams = Team::where('name', 'like', '%' . $input['name'] . '%')->get();
            if ($teams) {
                return response()->json([
                    'message' => 'success',
                    'data' => $teams,
                ], 200);
            }

            return response()->json([
                'message' => 'no_matches',
                'data' => null,
            ], 404);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e,
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{id}/info",
     *      summary="TeamController@showInfoSummary",
     *      description="Return brief details of a single team",
     *      tags={"Teams"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID - summary",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID - summary",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="introduction", type="string", example="info about the team"),
     *                  @OA\Property(property="url", type="string", example="http://placeholder"),
     *                  @OA\Property(property="img_url", type="string", example="http://placeholder"),
     *                  @OA\Property(property="summary", type="string", example="Summary"),
     *                  @OA\Property(property="durs", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="tools", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="publications", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="collections", type="array", example="{}", @OA\Items()),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function showInfoSummary(Request $request, int $id): JsonResponse
    {
        try {
            $team = Team::select('id', 'name', 'member_of', 'is_provider', 'introduction', 'url', 'service', 'team_logo')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
                ])->with(['aliases' => function ($query) {
                    $query->select(['id', 'name']);
                }
                ])->first();

            if (!$team) {
                throw new NotFoundException();
            }
            $service = array_filter(explode(",", $team->service));

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => [
                    'id' => $team->id,
                    'is_provider' => $team->is_provider,
                    'team_logo' => (is_null($team->team_logo) || strlen(trim($team->team_logo)) === 0) ? '' : (preg_match('/^https?:\/\//', $team->team_logo) ? $team->team_logo : Config::get('services.media.base_url') . $team->team_logo),
                    'url' => $team->url,
                    'service' => $service === [] ? null : $service,
                    'name' => $team->name,
                    'member_of' => $team->member_of,
                    'introduction' => $team->introduction,
                    'aliases' => $team->aliases,
                ],
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{id}/summary",
     *      summary="TeamController@showSummary",
     *      description="Return a single team summary (excluding datasets for performance reasons) for use in Data Provider view",
     *      tags={"Teams"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID - summary",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID - summary",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="introduction", type="string", example="info about the team"),
     *                  @OA\Property(property="url", type="string", example="http://placeholder"),
     *                  @OA\Property(property="img_url", type="string", example="http://placeholder"),
     *                  @OA\Property(property="summary", type="string", example="Summary"),
     *                  @OA\Property(property="durs", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="tools", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="publications", type="array", example="{}", @OA\Items()),
     *                  @OA\Property(property="collections", type="array", example="{}", @OA\Items()),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function showSummary(Request $request, int $id): JsonResponse
    {
        try {
            $team = Team::select('id', 'name', 'member_of', 'is_provider', 'introduction', 'url', 'service', 'team_logo')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
                ])->with(['aliases' => function ($query) {
                    $query->select(['id', 'name']);
                }
                ])->first();

            if (!$team) {
                throw new NotFoundException();
            }
            $service = array_filter(explode(",", $team->service));

            // Collections: get all active collections owned/associated linked with datasets/durs/tools/publications
            $collectionHasDatasets = $this->linkCollectionsWithDatasetsByTeamId($id);
            $collectionHasDurs = $this->linkCollectionsWithDursByTeamId($id);
            $associatedDurs = $this->associatedDurs($id, $collectionHasDurs['dur_ids']);
            $collectionHasTools = $this->linkCollectionsWithToolsByTeamId($id);
            $associatedTools = $this->associatedTools($id, $collectionHasTools['tool_ids']);
            $collectionHasPublications = $this->linkCollectionsWithPublicationsByTeamId($id);
            $associatedPublications = $this->associatedPublications($id, $collectionHasPublications['publication_ids']);

            // Durs: get all active durs owned/associated linked with datasets
            $durs = $this->linkDursByTeamId($id);

            // Tools: get all active tools owned/associated linked with datasets
            $tools = $this->linkToolsByTeamId($id);

            // Publications: get all active publications owned/associated linked with datasets
            $publications = $this->linkPublicationsByTeamId($id);

            // associated Datasets
            $this->associatedDatasetIds = array_values(array_unique($this->associatedDatasetIds));

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => [
                    'id' => $team->id,
                    'is_provider' => $team->is_provider,
                    'team_logo' => (is_null($team->team_logo) || strlen(trim($team->team_logo)) === 0)
                        ? ''
                        : (preg_match('/^https?:\/\//', $team->team_logo) ? $team->team_logo : Config::get('services.media.base_url') . $team->team_logo),
                    'url' => $team->url,
                    'service' => $service === [] ? null : $service,
                    'name' => $team->name,
                    'member_of' => $team->member_of,
                    'introduction' => $team->introduction,
                    'durs' => $durs['owned'],
                    'associated_durs' => collect(array_merge($durs['associated'], $associatedDurs['associated']))->unique('id')->values()->all(),
                    'tools' => $tools['owned'],
                    'associated_tools' => collect(array_merge($tools['associated'], $associatedTools['associated']))->unique('id')->values()->all(),
                    'publications' => $publications['owned'],
                    'associated_publications' => collect(array_merge($publications['associated'], $associatedPublications['associated']))->unique('id')->values()->all(),
                    'collections' => $collectionHasDatasets['owned'],
                    'associated_collections' => collect(array_merge($collectionHasDatasets['associated'], $collectionHasDurs['associated'], $collectionHasTools['associated'], $collectionHasPublications['associated']))->unique('id')->values()->all(),
                    'aliases' => $team->aliases,
                    'associated_datasets' => $this->associatedDatasets($this->associatedDatasetIds ?? []),
                ],
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function linkCollectionsWithPublicationsByTeamId(int $teamId)
    {
        $linkCollections = DB::select(
            'SELECT c.id, c.name, c.image_link, c.created_at, c.updated_at, c.public, c.team_id, p.id as p_id, tt.id as tt_id, tt.name as tt_name
            FROM collections c
            LEFT JOIN teams tt ON tt.id = c.team_id
            LEFT JOIN collection_has_publications chp ON chp.collection_id = c.id
            LEFT JOIN publications p ON p.id = chp.publication_id
            WHERE c.status = ?
            AND (
                c.team_id = ?
                OR (p.team_id = ? AND p.status = ?)
            )',
            [Collection::STATUS_ACTIVE, $teamId, $teamId, Publication::STATUS_ACTIVE]
        );

        $publicationIds = collect($linkCollections)->pluck('p_id')->filter()->unique()->values()->toArray();

        $mapped = collect($linkCollections)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'          => $group->first()->id,
                'name'        => $group->first()->name,
                'image_link'  => ($group->first()->image_link && !preg_match('/^https?:\/\//', $group->first()->image_link))
                    ? Config::get('services.media.base_url') . $group->first()->image_link
                    : $group->first()->image_link,
                'created_at'  => $group->first()->created_at,
                'updated_at'  => $group->first()->updated_at,
                'public'      => $group->first()->public,
                'relation'    => $group->first()->team_id === $teamId ? 'owned' : 'associated',
                'team'        => $group->first()->tt_id ? [
                    'id'   => $group->first()->tt_id,
                    'name' => $group->first()->tt_name,
                ] : null,
            ]);

        return [
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
            'publication_ids'    => $publicationIds,
        ];
    }

    private function associatedPublications(int $teamId, array $publicationIds)
    {
        if (!$publicationIds || count($publicationIds) === 0) {
            return [
                'associated' => [],
            ];
        }

        $linkPublications = DB::select(
            'SELECT p.id, p.paper_title, p.authors, p.url, p.team_id, tt.id as tt_id, tt.name as tt_name
            FROM publications p
            LEFT JOIN teams tt ON tt.id = p.team_id
            WHERE p.status = ? AND p.team_id != ? AND p.id IN (' . implode(',', $publicationIds) . ')',
            [Publication::STATUS_ACTIVE, $teamId]
        );

        $mapped = collect($linkPublications)
            ->map(fn ($item) => [
                'id'          => $item->id,
                'paper_title' => $item->paper_title,
                'authors'     => $item->authors,
                'url'         => $item->url,
                'relation'    => 'associated',
                'team'        => $item->tt_id ? [
                    'id'   => $item->tt_id,
                    'name' => $item->tt_name,
                ] : null,
            ])
            ->values()
            ->all();

        return [
            'associated' => $mapped,
        ];
    }

    private function linkCollectionsWithToolsByTeamId(int $teamId)
    {
        $linkCollections = DB::select(
            'SELECT c.id, c.name, c.image_link, c.created_at, c.updated_at, c.public, c.team_id, t.id as t_id, tt.id tt_id, tt.name tt_name
            FROM collections c
            LEFT JOIN teams tt ON tt.id = c.team_id
            LEFT JOIN collection_has_tools cht ON cht.collection_id = c.id
            LEFT JOIN tools t ON t.id = cht.tool_id
            WHERE c.status = ?
            AND (
                c.team_id = ?
                OR (t.team_id = ? AND t.status = ?)
            )',
            [Collection::STATUS_ACTIVE, $teamId, $teamId, Tool::STATUS_ACTIVE]
        );

        $toolIds = collect($linkCollections)->pluck('t_id')->filter()->unique()->values()->toArray();

        $mapped = collect($linkCollections)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'          => $group->first()->id,
                'name'        => $group->first()->name,
                'image_link'  => ($group->first()->image_link && !preg_match('/^https?:\/\//', $group->first()->image_link))
                    ? Config::get('services.media.base_url') . $group->first()->image_link
                    : $group->first()->image_link,
                'created_at'  => $group->first()->created_at,
                'updated_at'  => $group->first()->updated_at,
                'public'      => $group->first()->public,
                'relation'    => $group->first()->team_id === $teamId ? 'owned' : 'associated',
                'team'       => $group->first()->tt_id ? [
                    'id'   => $group->first()->tt_id,
                    'name' => $group->first()->tt_name,
                ] : null,
            ]);

        return [
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
            'tool_ids'    => $toolIds,
        ];
    }

    public function associatedTools(int $teamId, array $toolIds)
    {
        if (!$toolIds || count($toolIds) === 0) {
            return [
                'associated' => [],
            ];
        }

        $linkTools = DB::select(
            'SELECT t.id, t.name, t.user_id, t.created_at, t.team_id, tt.id tt_id, tt.name tt_name
            FROM tools t
            LEFT JOIN teams tt ON tt.id = t.team_id
            WHERE t.status = ? AND t.team_id != ? AND t.id IN (' . implode(',', $toolIds) . ')',
            [Tool::STATUS_ACTIVE, $teamId]
        );

        foreach ($linkTools as $tool) {
            $user = (User::where('id', $tool->user_id)
                ->select(
                    DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE firstname END as firstname"),
                    DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE lastname END as lastname"),
                    'is_admin'
                ))->first();

            if ($user) {
                $user = $user->toArray();
            } else {
                $user = [];
            }

            $arrayKeys = [
                'firstname',
                'lastname',
            ];
            $user = $this->checkEditArray($user, $arrayKeys);
            $tool->user = $user;
        }

        $mapped = collect($linkTools)
            ->map(fn ($item) => [
                'id'         => $item->id,
                'name'       => $item->name,
                'created_at' => $item->created_at,
                'user'       => $item->user,
                'relation'   => 'associated',
                'team'       => $item->tt_id ? [
                    'id'   => $item->tt_id,
                    'name' => $item->tt_name,
                ] : null,
            ])
            ->values()
            ->all();

        return [
            'associated' => $mapped,
        ];
    }

    private function linkCollectionsWithDatasetsByTeamId(int $teamId)
    {
        $linkCollections = DB::select(
            'SELECT c.id, c.name, c.image_link, c.created_at, c.updated_at, c.public, c.team_id, ds.id as ds_id, ds.team_id as ds_team_id, t.id as t_id, t.name as t_name
            FROM collections c
            LEFT JOIN teams t ON t.id = c.team_id
            LEFT JOIN collection_has_dataset_version chdv ON chdv.collection_id = c.id
            LEFT JOIN dataset_versions dv ON dv.id = chdv.dataset_version_id
            LEFT JOIN datasets ds ON ds.id = dv.dataset_id
            WHERE c.status = ?
            AND (
                c.team_id = ?
                OR (ds.team_id = ? AND ds.status = ?)
            )',
            [Collection::STATUS_ACTIVE, $teamId, $teamId, Dataset::STATUS_ACTIVE]
        );

        $this->associatedDatasetIds = collect($linkCollections)
            ->filter(fn ($row) => (int) $row->ds_team_id !== $teamId)
            ->pluck('ds_id')
            ->unique()
            ->values()
            ->all();

        $mapped = collect($linkCollections)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'         => $group->first()->id,
                'name'       => $group->first()->name,
                'image_link' => ($group->first()->image_link && !preg_match('/^https?:\/\//', $group->first()->image_link))
                    ? Config::get('services.media.base_url') . $group->first()->image_link
                    : $group->first()->image_link,
                'created_at' => $group->first()->created_at,
                'updated_at' => $group->first()->updated_at,
                'public'     => $group->first()->public,
                'relation'   => $group->first()->team_id === $teamId ? 'owned' : 'associated',
                'team'       => $group->first()->t_id ? [
                    'id'   => $group->first()->t_id,
                    'name' => $group->first()->t_name,
                ] : null,
            ]);

        return [
            'owned'      => $mapped->where('relation', 'owned')->values()->all(),
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
        ];
    }

    private function linkCollectionsWithDursByTeamId(int $teamId)
    {
        $linkCollections = DB::select(
            'SELECT c.id, c.name, c.image_link, c.created_at, c.updated_at, c.public, c.team_id, d.id as d_id, t.id as t_id, t.name as t_name
            FROM collections c
            LEFT JOIN teams t ON t.id = c.team_id
            LEFT JOIN collection_has_durs chd ON chd.collection_id = c.id
            LEFT JOIN dur d ON d.id = chd.dur_id
            WHERE c.status = ?
            AND (
                c.team_id = ?
                OR (d.team_id = ? AND d.status = ?)
            )',
            [Collection::STATUS_ACTIVE, $teamId, $teamId, Dur::STATUS_ACTIVE]
        );

        $durIds = collect($linkCollections)->pluck('d_id')->filter()->unique()->values()->toArray();

        $mapped = collect($linkCollections)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'          => $group->first()->id,
                'name'        => $group->first()->name,
                'image_link'  => ($group->first()->image_link && !preg_match('/^https?:\/\//', $group->first()->image_link))
                    ? Config::get('services.media.base_url') . $group->first()->image_link
                    : $group->first()->image_link,
                'created_at'  => $group->first()->created_at,
                'updated_at'  => $group->first()->updated_at,
                'public'      => $group->first()->public,
                'relation'    => $group->first()->team_id === $teamId ? 'owned' : 'associated',
                'team'        => $group->first()->t_id ? [
                    'id'   => $group->first()->t_id,
                    'name' => $group->first()->t_name,
                ] : null,
            ]);

        return [
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
            'dur_ids'    => $durIds,
        ];
    }

    private function associatedDurs(int $teamId, array $durIds)
    {
        if (!$durIds || count($durIds) === 0) {
            return [
                'associated' => [],
            ];
        }

        $linkDurs = DB::select(
            'SELECT dur.id, dur.project_title, dur.organisation_name, dur.status, dur.team_id, t.id as t_id, t.name as t_name
            FROM dur
            LEFT JOIN teams t ON t.id = dur.team_id
            WHERE dur.status = ? AND dur.team_id != ? AND dur.id IN (' . implode(',', $durIds) . ')',
            [Dur::STATUS_ACTIVE, $teamId]
        );

        $mapped = collect($linkDurs)
            ->map(fn ($item) => [
                'id'                => $item->id,
                'project_title'     => $item->project_title,
                'organisation_name' => $item->organisation_name,
                'relation'          => 'associated',
                'team'              => $item->t_id ? [
                    'id'   => $item->t_id,
                    'name' => $item->t_name,
                ] : null,
            ])
            ->values()
            ->all();

        return [
            'associated' => $mapped,
        ];
    }

    private function linkToolsByTeamId(int $teamId)
    {
        $linkTools = DB::select(
            'SELECT t.id, t.name, t.user_id, t.created_at, t.team_id, ds.id as ds_id, ds.team_id as ds_team_id, tt.id as tt_id, tt.name as tt_name
            FROM tools t
            LEFT JOIN teams tt ON tt.id = t.team_id
            LEFT JOIN dataset_version_has_tool dvht ON dvht.tool_id = t.id
            LEFT JOIN dataset_versions dv ON dv.id = dvht.dataset_version_id
            LEFT JOIN datasets ds ON ds.id = dv.dataset_id
            WHERE t.status = ?
            AND (
                t.team_id = ?
                OR (ds.team_id = ? AND ds.status = ?)
            )',
            [Tool::STATUS_ACTIVE, $teamId, $teamId, Dataset::STATUS_ACTIVE]
        );

        foreach ($linkTools as $tool) {
            $user = (User::where('id', $tool->user_id)
                ->select(
                    DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE firstname END as firstname"),
                    DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE lastname END as lastname"),
                    'is_admin'
                ))->first();

            if ($user) {
                $user = $user->toArray();
            } else {
                $user = [];
            }

            $arrayKeys = [
                'firstname',
                'lastname',
            ];
            $user = $this->checkEditArray($user, $arrayKeys);
            $tool->user = $user;
        }

        $this->associatedDatasetIds = collect($linkTools)
            ->filter(fn ($row) => (int) $row->ds_team_id !== $teamId)
            ->pluck('ds_id')
            ->unique()
            ->values()
            ->all();

        $mapped = collect($linkTools)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'         => $group->first()->id,
                'name'       => $group->first()->name,
                'created_at' => $group->first()->created_at,
                'user'       => $group->first()->user,
                'relation'   => $group->first()->team_id === $teamId ? 'owned' : 'associated',
                'team'       => $group->first()->tt_id ? [
                    'id'   => $group->first()->tt_id,
                    'name' => $group->first()->tt_name,
                ] : null,
            ]);

        return [
            'owned'      => $mapped->where('relation', 'owned')->values()->all(),
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
        ];
    }

    public function linkDursByTeamId(int $teamId)
    {
        $linkDurs = DB::select(
            'SELECT dur.id, dur.project_title, dur.organisation_name, dur.status, dur.team_id,
                    t.id as t_id, t.name as t_name,
                    ds.id as ds_id, ds.team_id as ds_team_id
            FROM dur
            LEFT JOIN teams t ON t.id = dur.team_id
            LEFT JOIN dur_has_dataset_version dhdv ON dhdv.dur_id = dur.id
            LEFT JOIN dataset_versions dv ON dv.id = dhdv.dataset_version_id
            LEFT JOIN datasets ds ON ds.id = dv.dataset_id
            WHERE dur.status = ?
            AND (
                dur.team_id = ?
                OR (ds.team_id = ? AND ds.status = ?)
            )',
            [Dur::STATUS_ACTIVE, $teamId, $teamId, Dataset::STATUS_ACTIVE]
        );

        $this->associatedDatasetIds = collect($linkDurs)
            ->filter(fn ($row) => (int) $row->ds_team_id !== $teamId)
            ->pluck('ds_id')
            ->unique()
            ->values()
            ->all();

        $mapped = collect($linkDurs)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'                => $group->first()->id,
                'project_title'     => $group->first()->project_title,
                'organisation_name' => $group->first()->organisation_name,
                'relation'          => $group->first()->team_id === $teamId ? 'owned' : 'associated',
                'team'              => $group->first()->t_id ? [
                    'id'   => $group->first()->t_id,
                    'name' => $group->first()->t_name,
                ] : null,
            ]);

        return [
            'owned'      => $mapped->where('relation', 'owned')->values()->all(),
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
        ];
    }

    private function linkPublicationsByTeamId(int $teamId)
    {
        $linkPublications = DB::select(
            'SELECT p.id, p.paper_title, p.authors, p.url, p.team_id as p_team_id, ds.id as ds_id, ds.team_id as ds_team_id, phdv.link_type as phdv_link_type, t.id as t_id, t.name as t_name
            FROM publications p
            LEFT JOIN teams t ON t.id = p.team_id
            LEFT JOIN publication_has_dataset_version phdv ON phdv.publication_id = p.id
            LEFT JOIN dataset_versions dv ON dv.id = phdv.dataset_version_id
            LEFT JOIN datasets ds ON ds.id = dv.dataset_id
            WHERE p.status = ?
            AND (
                p.team_id = ?
                OR (ds.team_id = ? AND ds.status = ?)
            )
            ORDER BY p.id ASC',
            [Publication::STATUS_ACTIVE, $teamId, $teamId, Dataset::STATUS_ACTIVE]
        );

        $this->associatedDatasetIds = collect($linkPublications)
            ->filter(fn ($row) => (int) $row->ds_team_id !== $teamId)
            ->pluck('ds_id')
            ->unique()
            ->values()
            ->all();

        $mapped = collect($linkPublications)
            ->groupBy('id')
            ->map(fn ($group) => [
                'id'          => $group->first()->id,
                'paper_title' => $group->first()->paper_title,
                'authors'     => $group->first()->authors,
                'url'         => $group->first()->url,
                'relation'    => $group->first()->p_team_id === $teamId ? 'owned' : 'associated',
                'team'        => $group->first()->t_id ? [
                    'id'   => $group->first()->t_id,
                    'name' => $group->first()->t_name,
                ] : null,
            ]);

        return [
            'owned'      => $mapped->where('relation', 'owned')->values()->all(),
            'associated' => $mapped->where('relation', 'associated')->values()->all(),
        ];
    }

    private function associatedDatasets(array $aDatasetIds)
    {
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)
                ->whereIn('id', $aDatasetIds)
                ->with('team:id,name')
                ->select([
                    'id','is_cohort_discovery', 'user_id', 'team_id', 'datasetid'
                ])->get();

        foreach ($datasets as $dataset) {
            $metadataSummary = $dataset->latestVersion()['metadata']['metadata']['summary'] ?? [];
            $dataset['title'] = $this->getValueByPossibleKeys($metadataSummary, ['title'], '');
            $dataset['populationSize'] = $this->getValueByPossibleKeys($metadataSummary, ['populationSize'], '');
            $dataset['datasetType'] = $this->getValueByPossibleKeys($metadataSummary, ['datasetType'], '');
            $dataset['relation'] = 'associated';
            $dataset['team'] = [
                'id'   => $dataset->team->id,
                'name' => $dataset->team->name,
            ];
        }

        return $datasets;
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{id}/datasets_cohort_discovery",
     *      summary="TeamController@showCohortDiscovery",
     *      description="Return whether at least one of a single team's datasets support cohort discovery for use in Data Provider view",
     *      tags={"Teams"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID - datasets",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID - datasets",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="datasets", type="array", example="{}", @OA\Items()),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function showCohortDiscovery(Request $request, int $id): JsonResponse
    {
        try {
            $team = Team::select('id', 'name', 'member_of', 'is_provider', 'introduction', 'url', 'service', 'team_logo')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
                ])->with(['aliases' => function ($query) {
                    $query->select(['id', 'name']);
                }
                ])->first();

            if (!$team) {
                throw new NotFoundException();
            }

            $ownedDatasets = Dataset::where(['team_id' => $id, 'status' => Dataset::STATUS_ACTIVE])
                ->select([
                    'is_cohort_discovery'
                ])->get();


            $supportsCohortDiscovery = in_array(true, array_pluck($ownedDatasets->toArray(), 'is_cohort_discovery'));

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => [
                    'id' => $team->id,
                    'supportsCohortDiscovery' => $supportsCohortDiscovery,
                ],
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{id}/datasets_summary",
     *      summary="TeamController@showDatasetsSummary",
     *      description="Return a single team datasets summary for use in Data Provider view",
     *      tags={"Teams"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DataProviderColl ID - datasets summary",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DataProviderColl ID - datasets summary",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="datasets", type="array", example="{}", @OA\Items()),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function showDatasetsSummary(Request $request, int $id): JsonResponse
    {
        try {
            $team = Team::select('id', 'name', 'member_of', 'is_provider', 'introduction', 'url', 'service', 'team_logo')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
                ])->with(['aliases' => function ($query) {
                    $query->select(['id', 'name']);
                }
                ])->first();

            if (!$team) {
                throw new NotFoundException();
            }

            $ownedDatasets = Dataset::where(['team_id' => $id, 'status' => Dataset::STATUS_ACTIVE])
                ->select([
                    'id','is_cohort_discovery', 'user_id', 'team_id', 'datasetid'
                ])->get();

            foreach ($ownedDatasets as $dataset) {

                $metadataSummary = $dataset->latestVersion()['metadata']['metadata']['summary'] ?? [];
                $dataset['title'] = $this->getValueByPossibleKeys($metadataSummary, ['title'], '');
                $dataset['populationSize'] = $this->getValueByPossibleKeys($metadataSummary, ['populationSize'], '');
                $dataset['datasetType'] = $this->getValueByPossibleKeys($metadataSummary, ['datasetType'], '');
            }
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => [
                    'id' => $team->id,
                    'datasets' => $ownedDatasets,
                ],
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/teams",
     *      tags={"Teams"},
     *      summary="TeamController@store",
     *      description="Creates a new team",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Team details",
     *          @OA\JsonContent(
     *              required={
     *                  "name",
     *                  "enabled",
     *                  "allows_messaging",
     *                  "workflow_enabled",
     *                  "access_requests_management",
     *                  "uses_5_safes",
     *                  "member_of",
     *                  "contact_point",
     *                  "application_form_updated_by",
     *                  "notifications",
     *              },
     *              @OA\Property(property="name", type="string", example="someName"),
     *              @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *              @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *              @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *              @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *              @OA\Property(property="is_admin", type="boolean", example="1"),
     *              @OA\Property(property="member_of", type="string", example="someOrg"),
     *              @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *              @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *              @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *              @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="users", type="array", example="[1, 2]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="is_question_bank", type="boolean", example="1"),
     *              @OA\Property(property="is_provider", type="boolean", example="1"),
     *              @OA\Property(property="url", type="string", example="https://example/image.jpg"),
     *              @OA\Property(property="introduction", type="string", example="info about the team"),
     *              @OA\Property(property="dar_modal_header", type="string", example="dar info"),
     *              @OA\Property(property="dar_modal_content", type="string", example="dar info"),
     *              @OA\Property(property="dar_modal_footer", type="string", example="dar info"),
     *              @OA\Property(property="service", type="string", example="https://example"),
     *              @OA\Property(property="aliases", type="array", example="[1, 2]", @OA\Items(type="array", @OA\Items())),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example="100")
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
    public function store(CreateTeam $request): JsonResponse
    {
        $team = null;
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        $arrayTeam = array_filter($input, function ($key) {
            return $key !== 'notifications' || $key !== 'users';
        }, ARRAY_FILTER_USE_KEY);

        $arrayTeam['name'] = formatCleanInput($input['name']);
        $arrayTeam['pid'] = (string) Str::uuid();

        $arrayTeamNotification = $input['notifications'] ?? [];
        $arrayTeamAlias = $input['aliases'] ?? [];
        $arrayTeamUsers = $input['users'];
        $superAdminIds = User::where('is_admin', true)->pluck('id');
        $team = Team::create($arrayTeam);

        try {
            if ($team) {
                foreach ($arrayTeamNotification as $value) {
                    TeamHasNotification::updateOrCreate([
                        'team_id' => (int)$team->id,
                        'notification_id' => (int)$value,
                    ]);
                }

                $arrayTeamAlias && $this->updateTeamAlias((int)$team->id, $arrayTeamAlias);

                //make sure the super admin is added to this team on creation
                foreach ($superAdminIds as $adminId) {
                    TeamHasUser::create(
                        ['team_id' => $team->id, 'user_id' => $adminId],
                    );
                }

                $roles = Role::where(['name' => 'custodian.team.admin'])->first();
                foreach ($arrayTeamUsers as $value) {
                    $teamHasUsers = TeamHasUser::create([
                        'team_id' => (int)$team->id,
                        'user_id' => (int)$value,
                    ]);

                    TeamUserHasRole::updateOrCreate([
                        'team_has_user_id' => (int)$teamHasUsers->id,
                        'role_id' => (int)$roles->id,
                    ]);
                }
            } else {
                throw new NotFoundException();
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team ' . $team->id . ' created',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $team->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $team->id,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }

    }

    /**
     * @OA\Put(
     *      path="/api/v1/teams/{teamId}",
     *      tags={"Teams"},
     *      summary="TeamController@update",
     *      description="Update a team",
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
     *      @OA\RequestBody(
     *          required=true,
     *          description="Team definition",
     *          @OA\JsonContent(
     *              required={
     *                  "name",
     *                  "enabled",
     *                  "allows_messaging",
     *                  "workflow_enabled",
     *                  "access_requests_management",
     *                  "uses_5_safes",
     *                  "member_of",
     *                  "contact_point",
     *                  "application_form_updated_by",
     *                  "notifications",
     *              },
     *              @OA\Property(property="name", type="string", example="someName"),
     *              @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *              @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *              @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *              @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *              @OA\Property(property="is_admin", type="boolean", example="1"),
     *              @OA\Property(property="member_of", type="string", example="someOrg"),
     *              @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *              @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *              @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *              @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="users", type="array", example="[1, 2]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="is_question_bank", type="boolean", example="1"),
     *              @OA\Property(property="is_provider", type="boolean", example="1"),
     *              @OA\Property(property="url", type="string", example="https://example/image.jpg"),
     *              @OA\Property(property="introduction", type="string", example="info about the team"),
     *              @OA\Property(property="dar_modal_header", type="string", example="dar info"),
     *              @OA\Property(property="dar_modal_content", type="string", example="dar info"),
     *              @OA\Property(property="dar_modal_footer", type="string", example="dar info"),
     *              @OA\Property(property="service", type="string", example="https://example"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="someName"),
     *                  @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                  @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                  @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                  @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                  @OA\Property(property="is_admin", type="boolean", example="1"),
     *                  @OA\Property(property="member_of", type="string", example="someOrg"),
     *                  @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                  @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                  @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *                  @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
     *                  @OA\Property(property="is_question_bank", type="boolean", example="1"),
     *                  @OA\Property(property="is_provider", type="boolean", example="1"),
     *                  @OA\Property(property="url", type="string", example="https://example/image.jpg"),
     *                  @OA\Property(property="introduction", type="string", example="info about the team"),
     *                  @OA\Property(property="dar_modal_header", type="string", example="dar info"),
*                       @OA\Property(property="dar_modal_content", type="string", example="dar info"),
     *                  @OA\Property(property="dar_modal_footer", type="string", example="dar info"),
     *                  @OA\Property(property="service", type="string", example="https://example"),
     *              )
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
    public function update(UpdateTeam $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $arrayKeys = [
                'name',
                'enabled',
                'allows_messaging',
                'workflow_enabled',
                'access_requests_management',
                'uses_5_safes',
                'is_admin',
                'member_of',
                'contact_point',
                'application_form_updated_by',
                'application_form_updated_on',
                'is_question_bank',
                'is_provider',
                'url',
                'introduction',
                'team_logo',
                'dar_modal_header',
                'dar_modal_content',
                'dar_modal_footer',
                'service',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            if (array_key_exists('name', $input)) {
                $array['name'] = formatCleanInput($input['name']);
            }
            Team::where('id', $teamId)->update($array);

            $arrayTeamNotification = array_key_exists('notifications', $input) ? $input['notifications'] : [];

            TeamHasNotification::where('team_id', $teamId)->delete();
            foreach ($arrayTeamNotification as $value) {
                TeamHasNotification::updateOrCreate([
                    'team_id' => (int) $teamId,
                    'notification_id' => (int) $value,
                ]);
            }

            $arrayTeamAlias = $input['aliases'] ?? [];
            $this->updateTeamAlias((int)$teamId, $arrayTeamAlias);

            $users = array_key_exists('users', $input) ? $input['users'] : [];
            $this->updateTeamAdminUsers($teamId, $users);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team ' . $teamId . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => Team::where('id', $teamId)->first(),
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => (int)$teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/teams/{teamId}",
     *      tags={"Teams"},
     *      summary="TeamController@edit",
     *      description="Edit a team",
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
     *      @OA\RequestBody(
     *          required=true,
     *          description="Team definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="someName"),
     *              @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *              @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *              @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *              @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *              @OA\Property(property="is_admin", type="boolean", example="1"),
     *              @OA\Property(property="member_of", type="string", example="someOrg"),
     *              @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *              @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *              @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *              @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="is_question_bank", type="boolean", example="1"),
     *              @OA\Property(property="is_provider", type="boolean", example="1"),
     *              @OA\Property(property="url", type="string", example="https://example/image.jpg"),
     *              @OA\Property(property="introduction", type="string", example="info about the team"),
     *              @OA\Property(property="dar_modal_header", type="string", example="dar info"),
     *              @OA\Property(property="dar_modal_content", type="string", example="dar info"),
     *              @OA\Property(property="dar_modal_footer", type="string", example="dar info"),
     *              @OA\Property(property="service", type="string", example="https://example"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="someName"),
     *                  @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                  @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                  @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                  @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                  @OA\Property(property="is_admin", type="boolean", example="1"),
     *                  @OA\Property(property="member_of", type="string", example="someOrg"),
     *                  @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                  @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                  @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *                  @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
     *                  @OA\Property(property="is_question_bank", type="boolean", example="1"),
     *                  @OA\Property(property="is_provider", type="boolean", example="1"),
     *                  @OA\Property(property="url", type="string", example="https://example/image.jpg"),
     *                  @OA\Property(property="introduction", type="string", example="info about the team"),
     *                  @OA\Property(property="dar_modal_header", type="string", example="dar info"),
     *                  @OA\Property(property="dar_modal_content", type="string", example="dar info"),
     *                  @OA\Property(property="dar_modal_footer", type="string", example="dar info"),
     *                  @OA\Property(property="service", type="string", example="https://example"),
     *              )
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
    public function edit(EditTeam $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        $this->checkAccess($input, $teamId, null, 'team');
        try {
            $arrayKeys = [
                'name',
                'enabled',
                'allows_messaging',
                'workflow_enabled',
                'access_requests_management',
                'uses_5_safes',
                'is_admin',
                'member_of',
                'contact_point',
                'application_form_updated_by',
                'application_form_updated_on',
                'is_question_bank',
                'is_provider',
                'url',
                'introduction',
                'team_logo',
                'dar_modal_header',
                'dar_modal_content',
                'dar_modal_footer',
                'service',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            if (array_key_exists('name', $input)) {
                $array['name'] = formatCleanInput($input['name']);
            }
            Team::where('id', $teamId)->update($array);

            $arrayTeamNotification = array_key_exists('notifications', $input) ? $input['notifications'] : [];

            TeamHasNotification::where('team_id', $teamId)->delete();
            foreach ($arrayTeamNotification as $value) {
                TeamHasNotification::updateOrCreate([
                    'team_id' => (int)$teamId,
                    'notification_id' => (int)$value,
                ]);
            }

            $arrayTeamAlias = $input['aliases'] ?? [];
            $this->updateTeamAlias((int)$teamId, $arrayTeamAlias);

            $users = array_key_exists('users', $input) ? $input['users'] : [];
            $this->updateTeamAdminUsers($teamId, $users);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team ' . $teamId . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Team::where('id', $teamId)->first(),
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
     *      path="/api/v1/teams/{teamId}",
     *      tags={"Teams"},
     *      summary="TeamController@destroy",
     *      description="Delete a team",
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
    public function destroy(DeleteTeam $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $team = Team::findOrFail($teamId);
            $existsDatasets = Dataset::where('team_id', $teamId)->select('id')->first();

            if (!is_null($existsDatasets)) {
                throw new Exception('The team cannot be deleted as there are datasets currently assigned to it.');
            }

            TeamHasNotification::where('team_id', $teamId)->delete();

            $team->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team ' . $teamId . ' deleted',
            ]);

            throw new NotFoundException();
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

    public function datasets(Request $request, int $teamId): JsonResponse
    {
        try {
            $filterStatus = $request->query('status', null);
            $datasetId = $request->query('dataset_id', null);
            $mongoPId = $request->query('mongo_pid', null);
            $withMetadata = $request->boolean('with_metadata', true);

            $sort = $request->query('sort', 'created:desc');

            $tmp = explode(':', $sort);
            $sortField = $tmp[0];
            $sortDirection = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';

            $sortOnMetadata = str_starts_with($sortField, 'metadata.');
            $allFields = collect(Dataset::first())->keys()->toArray();
            if (!$sortOnMetadata && count($allFields) > 0 && !in_array($sortField, $allFields)) {
                return response()->json([
                    'message' => '\"' . $sortField . '\" is not a valid field to sort on',
                ], 400);
            }

            $validDirections = ['desc', 'asc'];

            if (!in_array($sortDirection, $validDirections)) {
                return response()->json([
                    'message' => 'Sort direction must be either: ' .
                        implode(' OR ', $validDirections) .
                        '. Not "' . $sortDirection . '"',
                ], 400);
            }

            $filterTitle = $request->query('title', null);

            $matches = [];

            $datasets = Dataset::where('team_id', $teamId)
                ->when($datasetId, function ($query) use ($datasetId) {
                    return $query->where('datasetid', '=', $datasetId);
                })
                ->when($mongoPId, function ($query) use ($mongoPId) {
                    return $query->where('mongo_pid', '=', $mongoPId);
                })
                // LS - Reworked from original in DatasetsController@index, as
                // that is incorrect and flawed logic
                ->when(
                    $request->has('withTrashed'),
                    function ($query) {
                        return $query->withTrashed();
                    }
                )
                // LS - This is how it should be done, other places we currently
                // use both deleted_at and ARCHIVED as a status field. deleted_at
                // should denote a record has _actually_ been deleted, and therefore
                // hidden.
                ->when($filterStatus, function ($query) use ($filterStatus) {
                    return $query->where('status', '=', $filterStatus);
                })
                ->select(['id'])->get();

            foreach ($datasets as $d) {
                $matches[] = $d->id;
            }

            if (!empty($filterTitle)) {
                $titleMatches = [];

                foreach ($matches as $m) {
                    $version = DatasetVersion::where('dataset_id', $m)
                        ->filterTitle($filterTitle)
                        ->select('dataset_id')
                        ->when(
                            $request->has('withTrashed'),
                            function ($query) {
                                return $query->withTrashed();
                            }
                        )
                        ->first();

                    if ($version) {
                        $titleMatches[] = $version->dataset_id;
                    }
                }

                $matches = array_intersect($matches, $titleMatches);
            }

            $perPage = request('per_page', Config::get('constants.per_page'));

            $datasets = Dataset::whereIn('id', $matches)
                ->when($withMetadata, fn ($query) => $query->with('latestMetadata'))
                ->when(
                    $request->has('withTrashed'),
                    function ($query) {
                        return $query->withTrashed();
                    }
                )
                ->when($filterStatus, function ($query) use ($filterStatus) {
                    return $query->where('status', '=', $filterStatus);
                })
                ->when(
                    $sortOnMetadata,
                    fn ($query) => $query->orderByMetadata($sortField, $sortDirection),
                    fn ($query) => $query->orderBy($sortField, $sortDirection)
                )
                ->paginate($perPage, ['*'], 'page');

            foreach ($datasets as &$d) {
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

            return response()->json(
                $datasets
            );

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function updateTeamAdminUsers(int $teamId, array $users)
    {
        // check in team
        $roleId = Role::where(['name' => 'custodian.team.admin'])->first()->id;
        $existTeamHasUsers = TeamHasUser::where([
            'team_id' => (int)$teamId,
        ])->get();
        foreach ($existTeamHasUsers as $existTeamHasUser) {
            if (in_array($existTeamHasUser->user_id, $users)) {
                continue;
            }

            TeamUserHasRole::where([
                'team_has_user_id' => (int)$existTeamHasUser->id,
                'role_id' => (int)$roleId,
            ])->delete();
        }

        foreach ($users as $user) {
            $teamhasUser = TeamHasUser::updateOrCreate([
                'team_id' => (int)$teamId,
                'user_id' => (int)$user,
            ]);

            TeamUserHasRole::updateOrCreate([
                'team_has_user_id' => (int)$teamhasUser->id,
                'role_id' => (int)$roleId,
            ]);
        }
    }

    private function updateTeamAlias(int $teamId, array $arrayTeamAlias): void
    {
        TeamHasAlias::where('team_id', $teamId)->delete();

        foreach ($arrayTeamAlias as $aliasId) {
            TeamHasAlias::updateOrCreate([
                'team_id' => (int)$teamId,
                'alias_id' => (int)$aliasId,
            ]);
        }
    }

}
