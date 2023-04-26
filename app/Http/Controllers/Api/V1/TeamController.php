<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Carbon\Carbon;
use App\Models\Team;
use App\Models\TeamHasNotification;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Traits\TeamTransformation;


class TeamController extends Controller
{
    use TeamTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/teams",
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
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $teams = Team::where('enabled', 1)->with('users')->get()->toArray();

        $response = $this->getTeams($teams);

        return response()->json([
            'message' => 'success',
            'data' => $response,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{id}",
     *      summary="Return a single team",
     *      description="Return a single team",
     *      security={{"bearerAuth":{}}},
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
     *                  @OA\Property(property="notifications", type="array", example="[]"),
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
    public function show(Request $request, int $id)
    {
        $team = Team::with('notifications')->where('id', $id)->firstOrFail();

        if ($team) {
            $userTeam = Team::where('id', $id)->with('users')->get()->toArray();
            return response()->json([
                'message' => 'success',
                'data' => $this->getTeams($userTeam),
            ], 200);
        }

        return response()->json([
            'message' => 'not found'
        ], 404);
    }

    /**
     * @OA\Post(
     *      path="/api/v1/teams",
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
     *              @OA\Property(property="notifications", type="array", example="[111, 222]"),
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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'enabled' => 'required',
            'allows_messaging' => 'required',
            'workflow_enabled' => 'required',
            'access_requests_management' => 'required',
            'uses_5_safes' => 'required',
            'is_admin' => 'required',
            'member_of' => 'required',
            'contact_point' => 'required',
            'application_form_updated_by' => 'required',
            'application_form_updated_on' => 'required',
            'notifications' => 'required',
        ]);

        $input = $request->all();
        $arrayTeam = array_filter($input, function ($key) {
            return $key !== 'notifications';
        }, ARRAY_FILTER_USE_KEY);
        $arrayTeamNotification = $input['notifications'];

        $team = Team::create($arrayTeam);

        if ($team) {
            foreach ($arrayTeamNotification as $value) {
                TeamHasNotification::updateOrCreate([
                    'team_id' => (int) $team->id,
                    'notification_id' => (int) $value,
                ]);
            }
        } else {
            return response()->json([
                'message' => 'error',
            ], 500);
        }

        return response()->json([
            'message' => 'success',
            'data' => $team->id,
        ], 200);
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/teams/{id}",
     *      summary="Update a team",
     *      description="Update a team",
     *      security={{"bearerAuth":{}}},
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
     *              @OA\Property(property="notifications", type="array", example="[111, 222]"),
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
     *                  @OA\Property(property="notifications", type="array", example="[111, 222]"),
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
    public function update(Request $request, int $team)
    {
        $request->validate([
            'name' => 'required',
            'enabled' => 'required',
            'allows_messaging' => 'required',
            'workflow_enabled' => 'required',
            'access_requests_management' => 'required',
            'uses_5_safes' => 'required',
            'is_admin' => 'required',
            'member_of' => 'required',
            'contact_point' => 'required',
            'application_form_updated_by' => 'required',
            'application_form_updated_on' => 'required',
            'notifications' => 'required',
        ]);

        $team = Team::findOrFail($team);
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

        $arrayTeamNotification = $body['notifications'];
        TeamHasNotification::where('team_id', $team->id)->delete();
        foreach ($arrayTeamNotification as $value) {
            TeamHasNotification::updateOrCreate([
                'team_id' => (int) $team->id,
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

        return response()->json([
            'message' => 'not found',
        ], 404);
    }
    
    /**
     * @OA\Delete(
     *      path="/api/v1/teams/{id}",
     *      summary="Delete a team",
     *      description="Delete a team",
     *      security={{"bearerAuth":{}}},
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
    public function destroy(Request $request, int $team): mixed
    {
        try {
            $team = Team::findOrFail($team);
            if ($team) {
                TeamHasNotification::where('team_id', $team->id)->delete();
                $team->delete();
                
                return response()->json([
                    'message' => 'success',
                ], 200);
            }

            return response()->json([
                'message' => 'not found',
            ], 404);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}