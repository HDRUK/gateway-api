<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataAccessTemplate\GetTeamDataAccessTemplate;
use App\Http\Traits\RequestTransformation;
use App\Models\DataAccessTemplate;

class TeamDataAccessTemplateController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/dar/templates",
     *      summary="List of dar templates belonging to a team",
     *      description="List of dar templates belonging to a team",
     *      tags={"TeamDataAccessTemplate"},
     *      summary="TeamDataAccessTemplate@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="team_id", type="integer", example="1"),
     *                      @OA\Property(property="user_id", type="integer", example="1"),
     *                      @OA\Property(property="published", type="boolean", example="true"),
     *                      @OA\Property(property="locked", type="boolean", example="false"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(GetTeamDataAccessTemplate $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $templates = DataAccessTemplate::where('team_id', $teamId)
            ->with('questions')
            ->paginate(
                Config::get('constants.per_page'),
                ['*'],
                'page'
            );

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate get all by team',
            ]);

            return response()->json(
                $templates
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

}
