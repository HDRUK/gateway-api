<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Mauro;
use App\Models\Team;
use Illuminate\Http\Request;
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


class TeamController extends Controller
{
    use TeamTransformation;
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/teams",
     *      tags={"Teams"},
     *      summary="List of teams",
     *      description="Returns a list of teams enabled on the system",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                      @OA\Property(property="name", type="string", example="someName"),
     *                      @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                      @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                      @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                      @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                      @OA\Property(property="is_admin", type="boolean", example="1"),
     *                      @OA\Property(property="member_of", type="string", example="someOrg"),
     *                      @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                      @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                      @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"), 
     *                      @OA\Property(property="mdm_folder_id", type="datetime", example="xxxxxxx"), 
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $teams = Team::where('enabled', 1)->with('users')->get()->toArray();

        $response = $this->getTeams($teams);

        return response()->json([
            'data' => $response,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}",
     *      tags={"Teams"},
     *      summary="Return a single team",
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
     *                  @OA\Property(property="mdm_folder_id", type="string", example="xxxx"),
     *                  @OA\Property(property="notifications", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
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
        $team = Team::with('notifications')->where('id', $teamId)->firstOrFail();

        if ($team) {
            $userTeam = Team::where('id', $teamId)->with(['users', 'notifications'])->get()->toArray();
            return response()->json([
                'message' => 'success',
                'data' => $this->getTeams($userTeam),
            ], 200);
        }

        throw new NotFoundException();
    }

    /**
     * @OA\Post(
     *      path="/api/v1/teams",
     *      tags={"Teams"},
     *      summary="Create a new team",
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
     *                  "is_admin",
     *                  "member_of",
     *                  "contact_point",
     *                  "application_form_updated_by",
     *                  "application_form_updated_on",
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
     *              @OA\Property(property="mdm_folder_id", type="string", example="xxxx"),
     *              @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
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
        try {
            $input = $request->all();
            $arrayTeam = array_filter($input, function ($key) {
                return $key !== 'notifications';
            }, ARRAY_FILTER_USE_KEY);
            $arrayTeamNotification = $input['notifications'];

            // create subfolder in mauro
            $mauroResponse = Mauro::createFolder(
                $input['name'],
                $input['name'],
                env('MAURO_PARENT_FOLDER_ID')
            );

            if(!array_key_exists('id',$mauroResponse)){
                return response()->json([
                    'message' => 'error',
                    'details' => $mauroResponse,
                ], 400);
            }



            $arrayTeam['mdm_folder_id'] = $mauroResponse['id'];
            $team = Team::create($arrayTeam);

            if ($team) {
                foreach ($arrayTeamNotification as $value) {
                    TeamHasNotification::updateOrCreate([
                        'team_id' => (int) $team->id,
                        'notification_id' => (int) $value,
                    ]);
                }
            } else {
                throw new NotFoundException();
            }

            return response()->json([
                'message' => 'success',
                'data' => $team->id,
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        
    }

    /**
     * @OA\Put(
     *      path="/api/v1/teams/{teamId}",
     *      tags={"Teams"},
     *      summary="Update a team",
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
     *                  "is_admin",
     *                  "member_of",
     *                  "contact_point",
     *                  "application_form_updated_by",
     *                  "application_form_updated_on",
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
     *              @OA\Property(property="mdm_folder_id", type="string", example="xxx"),
     *              @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
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
     *                  @OA\Property(property="mdm_folder_id", type="string", example="xxxx"),
     *                  @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
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
        $team = Team::findOrFail($teamId);
        $body = $request->post();
        $team->name = $body['name'];
        $team->enabled = $body['enabled'];
        $team->allows_messaging = $body['allows_messaging'];
        $team->workflow_enabled = $body['workflow_enabled'];
        $team->access_requests_management = $body['access_requests_management'];
        $team->uses_5_safes = $body['uses_5_safes'];
        $team->is_admin = $body['is_admin'];
        $team->member_of = $body['member_of'];
        $team->contact_point = $body['contact_point'];
        $team->application_form_updated_by = $body['application_form_updated_by'];
        $team->application_form_updated_on = $body['application_form_updated_on'];

        if (array_key_exists('mdm_folder_id', $body)) {
            $team->mdm_folder_id = $body['mdm_folder_id'];
        }

        $arrayTeamNotification = $body['notifications'];
        TeamHasNotification::where('team_id', $teamId)->delete();
        foreach ($arrayTeamNotification as $value) {
            TeamHasNotification::updateOrCreate([
                'team_id' => (int) $teamId,
                'notification_id' => (int) $value,
            ]);
        }

        if ($team->save()) {
            return response()->json([
                'message' => 'success',
                'data' => $team,
            ], 200);
        } else {
            return response()->json([
                'message' => 'error',
            ], 500);
        }

        throw new NotFoundException();
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/teams/{teamId}",
     *      tags={"Teams"},
     *      summary="Edit a team",
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
     *              @OA\Property(property="mdm_folder_id", type="string", example="xxxxx"),
     *              @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
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
     *                  @OA\Property(property="mdm_folder_id", type="string", example="xxxxx"),
     *                  @OA\Property(property="notifications", type="array", example="[111, 222]", @OA\Items(type="array", @OA\Items())),
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
        try {
            $input = $request->all();
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
                'mdm_folder_id',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Team::where('id', $teamId)->update($array);

            $arrayTeamNotification = array_key_exists('notifications', $input) ? $input['notifications'] : [];

            TeamHasNotification::where('team_id', $teamId)->delete();
            foreach ($arrayTeamNotification as $value) {
                TeamHasNotification::updateOrCreate([
                    'team_id' => (int) $teamId,
                    'notification_id' => (int) $value,
                ]);
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Team::where('id', $teamId)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/teams/{teamId}",
     *      tags={"Teams"},
     *      summary="Delete a team",
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
        try {
            $team = Team::findOrFail($teamId);
            if ($team) {
                TeamHasNotification::where('team_id', $teamId)->delete();

                $deletePermanently = false;
                if ($request->has('deletePermanently')) {
                    $deletePermanently = (bool) $request->query('deletePermanently');
                }

                // soft delete subfolder in mauro
                $result = Mauro::deleteFolder(
                    $team['mdm_folder_id'],
                    $deletePermanently,
                    env('MAURO_PARENT_FOLDER_ID')
                );

                if (!$result) {
                    throw new Exception('Mauro team deletion failed for id ' . $team['mdm_folder_id']);
                }

                $team->delete();

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}