<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;

use EnquiriesManagementController as EMC;

use App\Models\User;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\EnquiryThread;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Http\Controllers\Controller;

class EnquiryThreadController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/enquiry_threads",
     *      summary="List of EnquiryThread",
     *      description="Returns a list of EnquiryThreads from the system",
     *      tags={"EnquiryThread"},
     *      summary="EnquiryThread@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="user_id", type="integer", example="123"),
     *                  @OA\Property(property="team_id", type="integer", example="1234"),
     *                  @OA\Property(property="project_title", type="string", example="Project Title"),
     *                  @OA\Property(property="unique_id", type="string", example="sdlkfjslkf83992874"),
     *                  @OA\Property(property="enabled", type="boolean", example="true"),
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $perPage = request('perPage', Config::get('constants.per_page'));

            $enquiryThreads = EnquiryThread::paginate($perPage);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'EnquiryThread get all',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $enquiryThreads,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/enquiry_threads/{id}",
     *      summary="Return a single EnquiryThread",
     *      description="Return a single EnquiryThread",
     *      tags={"EnquiryThread"},
     *      summary="EnquiryThread@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="EnquiryThread id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="EnquiryThread id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="user_id", type="integer", example="123"),
     *                  @OA\Property(property="team_id", type="integer", example="1234"),
     *                  @OA\Property(property="project_title", type="string", example="Project Title"),
     *                  @OA\Property(property="unique_id", type="string", example="sdlkfjslkf83992874"),
     *                  @OA\Property(property="enabled", type="boolean", example="true"),
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
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $enquiryThread = EnquiryThread::where('id', $id)->get();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'EnquiryThread get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $enquiryThread,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/enquiry_threads",
     *      summary="Create a new EnquiryThread",
     *      description="Creates a new EnquiryThread",
     *      tags={"EnquiryThread"},
     *      summary="EnquiryThread@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="EnquiryThread definition",
     *          @OA\JsonContent(
     *              required={"user_id", "team_id", "project_title"},
     *                  @OA\Property(property="user_id", type="integer", example="123"),
     *                  @OA\Property(property="team_id", type="integer", example="1234"),
     *                  @OA\Property(property="project_title", type="string", example="Project Title"),
     *                  @OA\Property(property="enabled", type="boolean", example="true"),
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
    public function store(Request $request): JsonResponse
    {
        $enquiryThreadId = null;
        $enquiryMessageId = null;
        $usersToNotify = null;

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        $team = Team::where('id', $input['team_id'])->first();
        $user = User::where('id', $jwtUser['id'])->first();

        try {
            $payload = [
                'thread' => [
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'project_title' => $input['project_title'],
                    'unique_key' => md5(microtime() . $jwtUser['id']), // Not random, but should be unique
                    'is_dar_dialogue' => $input['is_dar_dialogue'],
                    'is_dar_review' => $input['is_dar_review'],
                    'datasets' => $this->mapDatasets($input['datasets']),
                    'enabled' => true,
                ],
                'message' => [
                    'from' => $input['from'],
                    'message_body' => [
                        '[[TEAM_NAME]]' => $team->name,
                        '[[USER_FIRST_NAME]]' => $user->firstname,
                        '[[USER_LAST_NAME]]' => $user->lastname,
                        '[[USER_ORGANISATION]]' => $user->organisation,
                        '[[PROJECT_TITLE]]' => $input['project_title'],
                        '[[RESEARCH_AIM]]' => $input['research_aim'],
                        '[[OTHER_DATASETS_YES_NO]]' => $input['other_datasets'],
                        '[[DATASETS_PARTS_YES_NO]]' => $input['dataset_parts_known'],
                        '[[FUNDING]]' => $input['funding'],
                        '[[PUBLIC_BENEFIT]]' => $input['potential_research_benefit'],
                        '[[CURRENT_YEAR]]' => date('Y'),
                    ],
                ],
            ];

            // For each dataset we need to determine if teams are responsible for the data providing
            // if not, then a separate enquiry thread and message are created for that team also.
            foreach ($payload['thread']['datasets'] as $d) {
                $t = Team::where('id', $d['team_id'])->first();

                $payload['thread']['team_id'] = $t->id;

                $enquiryThreadId = EMC::createEnquiryThread($payload['thread']);
                $enquiryMessageId = EMC::createEnquiryMessage($enquiryThreadId, $payload['message']);
                $usersToNotify[] = EMC::determineDARManagersFromTeamId($t->id, $enquiryThreadId);
            }

            if (empty($usersToNotify)) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'POST',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'EnquiryThread was created, but no custodian.dar.managers found to notify for thread ' .
                        $enquiryThreadId,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_BAD_REQUEST.message'),
                    'data' => null,
                ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
            }

            // Spawn email notifications to all DAR managers for this team
            EMC::sendEmail('dar.firstmessage', $payload, $usersToNotify, $jwtUser);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'EnquiryThread ' . $enquiryThreadId . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $enquiryThreadId,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
        } finally {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_BAD_REQUEST.message'),
                'data' => null,
            ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }
    }

    private function mapDatasets(array $datasets): array
    {
        $arr = [];

        foreach ($datasets as $dataset) {
            $ds = Dataset::with('latestMetadata')->where('id', $dataset['dataset_id'])->first();
            $datasetUrl = env('GATEWAY_URL') . '/dataset/' . $ds->id . '?section=1';

            $arr[] = [
                'title' => $ds->latestMetadata->metadata['metadata']['summary']['shortTitle'],
                'dataset_id' => $dataset['dataset_id'],
                'url' => $datasetUrl,
                'interest_type' => $dataset['interest_type'],
                'team_id' => $ds->team_id,
            ];
        }

        return $arr;
    }
}
