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
use Illuminate\Support\Arr;
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
     *                ),
     *             ),
     *             @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests?page=1"),
     *             @OA\Property(property="from", type="integer", example="1"),
     *             @OA\Property(property="last_page", type="integer", example="1"),
     *             @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests?page=1"),
     *             @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *             @OA\Property(property="next_page_url", type="string", example="null"),
     *             @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests"),
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
                ->with('users')
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
            $userTeam = Team::where('id', $teamId)->with(['users', 'notifications'])->get()->toArray();

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

            throw new Exception($e);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{id}/summary",
     *      summary="TeamController@showSummary",
     *      description="Return a single team summary for use in Data Provider view",
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
     *                  @OA\Property(property="datasets", type="array", example="{}", @OA\Items()),
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
            $dp = Team::select('id', 'name', 'member_of', 'is_provider', 'introduction', 'url', 'service', 'team_logo')
                ->where([
                    'id' => $id,
                    'enabled' => 1,
                ])->first();

            if (!$dp) {
                return response()->json([
                    'message' => 'Team not found or not enabled',
                    'data' => null,
                ], 404);
            }

            // This sets not only this->datasets, but also this->durs, publications, tools and collections
            $this->getDatasets($dp->id);

            $teamDurs = Dur::where(['team_id' => $id])->select('id')->get();
            foreach ($teamDurs as $teamDur) {
                if (!in_array($teamDur->id, $this->durs)) {
                    $this->durs[] = $teamDur->id;
                }
            }

            $tools = Tool::whereIn('id', $this->tools)
                ->where('status', Tool::STATUS_ACTIVE)
                ->select('id', 'name', 'user_id', 'created_at')
                ->get();
            foreach ($tools as $tool) {
                $user = User::where('id', $tool->user_id)
                    ->select(
                        DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE firstname END as firstname"),
                        DB::raw("CASE WHEN is_admin = 1 THEN '' ELSE lastname END as lastname"),
                        'is_admin'
                    )
                    ->first()
                    ->toArray();

                // Reduce the amount of data returned to the bare minimum
                $arrayKeys = [
                    'firstname',
                    'lastname',
                    'rquestroles',
                ];
                $user = $this->checkEditArray($user, $arrayKeys);
                $tool['user'] = $user;
            }

            $collections = Collection::whereIn('id', $this->collections)
                ->select('id', 'name', 'image_link', 'created_at', 'updated_at', 'status', 'public')
                ->get()
                ->toArray();

            $collections = array_map(function ($collection) {
                if ($collection['image_link'] && !preg_match('/^https?:\/\//', $collection->image_link)) {
                    $collection['image_link'] = Config::get('services.media.base_url') . $collection['image_link'];
                }
                return $collection;
            }, $collections);

            $collections = array_values(array_filter($collections, function ($collection) {
                return $collection['status'] === Collection::STATUS_ACTIVE && $collection['public'];
            }));

            $service = array_values(array_filter(explode(",", $dp->service)));

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team get ' . $id . ' summary',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => [
                    'id' => $dp->id,
                    'is_provider' => $dp->is_provider,
                    'team_logo' => (is_null($dp->team_logo) || strlen(trim($dp->team_logo)) === 0) ? '' : (preg_match('/^https?:\/\//', $dp->team_logo) ? $dp->team_logo : Config::get('services.media.base_url') . $dp->team_logo),
                    'url' => $dp->url,
                    'service' => empty($service) ? null : $service,
                    'name' => $dp->name,
                    'member_of' => $dp->member_of,
                    'introduction' => $dp->introduction,
                    'datasets' => $this->datasets,
                    'durs' => Dur::select('id', 'project_title', 'organisation_name', 'status', 'created_at', 'updated_at')
                        ->whereIn('id', $this->durs)
                        ->where('status', Dur::STATUS_ACTIVE)
                        ->get()
                        ->toArray(),
                    'tools' => $tools->toArray(),
                    // TODO: need to add in `link_type` from publication_has_dataset table.
                    'publications' => Publication::select('id', 'paper_title', 'authors', 'url')
                        ->whereIn('id', $this->publications)
                        ->where('status', Publication::STATUS_ACTIVE)
                        ->get()
                        ->toArray(),
                    'collections' => $collections,
                ],
            ]);
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
     *              @OA\Property(property="dar_modal_content", type="string", example="dar info"),
     *              @OA\Property(property="service", type="string", example="https://example"),
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

        $arrayTeamNotification = $input['notifications'];
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

                //make sure the super admin is added to this team on creation
                foreach($superAdminIds as $adminId) {
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
            ], 200);
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
     *              @OA\Property(property="dar_modal_content", type="string", example="dar info"),
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
     *                  @OA\Property(property="dar_modal_content", type="string", example="dar info"),
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
                'dar_modal_content',
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
     *              @OA\Property(property="dar_modal_content", type="string", example="dar info"),
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
     *                  @OA\Property(property="dar_modal_content", type="string", example="dar info"),
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
                'dar_modal_content',
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
            if ($team) {
                TeamHasNotification::where('team_id', $teamId)->delete();

                $deletePermanently = false;
                if ($request->has('deletePermanently')) {
                    $deletePermanently = (bool)$request->query('deletePermanently');
                }

                $team->delete();

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

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
            throw new Exception($e);
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

    public function getDatasets(int $teamId)
    {
        $datasets = Dataset::where(['team_id' => $teamId, 'status' => Dataset::STATUS_ACTIVE])->select(['id'])->get();

        foreach ($datasets as $dataset) {
            $this->checkingDataset($dataset->id);
        }
    }

    public function checkingDataset(int $datasetId)
    {
        $dataset = Dataset::where(['id' => $datasetId])->first();

        if (!$dataset) {
            return;
        }

        $version = $dataset->latestVersion();
        $withLinks = DatasetVersion::where('id', $version['id'])
            ->with(['linkedDatasetVersions'])
            ->first();

        if (!$withLinks) {
            return;
        }

        $dataset->setAttribute('versions', [$withLinks]);

        $metadataSummary = $dataset['versions'][0]['metadata']['metadata']['summary'] ?? [];

        $title = $this->getValueByPossibleKeys($metadataSummary, ['title'], '');
        $populationSize = $this->getValueByPossibleKeys($metadataSummary, ['populationSize'], -1);
        $datasetType = $this->getValueByPossibleKeys($metadataSummary, ['datasetType'], '');

        $this->datasets[] = [
            'id' => $dataset->id,
            'status' => $dataset->status,
            'title' => $title,
            'populationSize' => $populationSize,
            'datasetType' => $datasetType,
        ];

        $this->durs = array_unique(array_merge($this->durs, Arr::pluck($dataset->allDurs, 'id')));
        $this->publications = array_unique(array_merge($this->publications, Arr::pluck($dataset->allPublications, 'id')));
        $this->tools = array_unique(array_merge($this->tools, Arr::pluck($dataset->allTools, 'id')));
        $this->collections = array_unique(array_merge($this->collections, Arr::pluck($dataset->allCollections, 'id')));
    }
}
