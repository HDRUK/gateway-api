<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Carbon\Carbon;
use Config;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationReview;
use App\Models\DataAccessApplicationStatus;
use App\Models\Dataset;
use App\Models\Team;
use App\Models\TeamHasDataAccessApplication;

class TeamDataAccessApplicationController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/dar/applications",
     *      summary="List of dar applications belonging to a team",
     *      description="List of dar applications belonging to a team",
     *      tags={"TeamDataAccessApplication"},
     *      summary="TeamDataAccessApplication@index",
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
     *                      @OA\Property(property="applicant_id", type="integer", example="1"),
     *                      @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                      @OA\Property(property="approval_status", type="string", example="APPORVED"),
     *                      @OA\Property(property="project_title", type="string", example="A project"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $applicationIds = TeamHasDataAccessApplication::where('team_id', $teamId)
                ->select('dar_application_id')
                ->pluck('dar_application_id');

            $filterTitle = $request->query('project_title', null);
            $filterApproval = $request->query('approval_status', null);
            $filterSubmission = $request->query('submission_status', null);
            $filterAction = isset($input['action_required']) ?
                $request->boolean('action_required', null) : null;

            $applications = DataAccessApplication::whereIn('id', $applicationIds)
                ->when($filterTitle, function ($query) use ($filterTitle) {
                    return $query->where('project_title', 'LIKE', "%{$filterTitle}%");
                })
                ->when($filterApproval, function ($query) use ($filterApproval) {
                    return $query->where('approval_status', '=', $filterApproval);
                })
                ->when($filterSubmission, function ($query) use ($filterSubmission) {
                    return $query->where('submission_status', '=', $filterSubmission);
                })
                ->select(['id'])->get();

            $matches = [];
            foreach ($applications as $a) {
                $matches[] = $a->id;
            }

            if (!is_null($filterAction)) {
                $actionMatches = [];
                foreach ($matches as $m) {
                    $reviews = DataAccessApplicationReview::where('application_id', $m)
                        ->select(['resolved'])->pluck('resolved')->toArray();
                    $resolved = in_array(false, $reviews) ? false : true;

                    if ((bool) $filterAction === $resolved) {
                        $actionMatches[] = $m;
                    }
                }
                $matches = array_intersect($matches, $actionMatches);
            }

            $applications = DataAccessApplication::whereIn('id', $matches)
                ->with(['user:id,name,organisation','datasets'])
                ->applySorting()
                ->paginate(
                    Config::get('constants.per_page'),
                    ['*'],
                    'page'
                );

            foreach ($applications as $app) {
                foreach ($app['datasets'] as $d) {
                    $dataset = Dataset::where('id', $d['dataset_id'])->first();
                    $title = $dataset->getTitle();
                    $custodian = Team::where('id', $dataset->team_id)->select(['id','name'])->first();
                    $d['dataset_title'] = $title;
                    $d['custodian'] = $custodian;
                }

                $submissionAudit = DataAccessApplicationStatus::where([
                    'application_id' => $app->id,
                    'submission_status' => 'SUBMITTED',
                ])->first();
                if ($submissionAudit) {
                    $app['days_since_submission'] = $submissionAudit
                        ->updated_at
                        ->diffInDays(Carbon::today());
                } else {
                    $app['days_since_submission'] = null;
                }
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication get all by team',
            ]);

            return response()->json(
                $applications
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
     *    path="/api/v1/teams/{teamId}/dar/applications/count/{field}",
     *    operationId="count_unique_fields_dar_applications",
     *    tags={"TeamDataAccessApplications"},
     *    summary="TeamDataAccessApplicationController@count",
     *    description="Get Counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="field",
     *       in="path",
     *       description="name of the field to perform a count on",
     *       required=true,
     *       example="approval_status",
     *       @OA\Schema(
     *          type="string",
     *          description="approval status field",
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
    public function count(Request $request, int $teamId, string $field): JsonResponse
    {
        try {
            $applicationIds = TeamHasDataAccessApplication::where('team_id', $teamId)
                ->select('dar_application_id')
                ->pluck('dar_application_id');

            $counts = DataAccessApplication::whereIn('id', $applicationIds)
                ->select($field)
                ->get()
                ->groupBy($field)
                ->map->count();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Team DAR application count",
            ]);

            return response()->json([
                "data" => $counts
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
