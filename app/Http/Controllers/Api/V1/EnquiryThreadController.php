<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use App\Models\EnquiryThread;
use App\Models\DataProviderColl;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\EnquiriesTrait;
use App\Http\Controllers\Controller;

class EnquiryThreadController extends Controller
{
    use EnquiriesTrait;

    /**
     * @OA\Get(
     *      path="/api/v1/enquiry_threads",
     *      summary="List of EnquiryThread",
     *      description="Returns a list of EnquiryThreads from the system",
     *      tags={"EnquiryThread"},
     *      summary="EnquiryThread@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="per page",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="per page",
     *          ),
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
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $perPage = request('per_page', Config::get('constants.per_page'));

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
     *                  @OA\Property(property="project_title", type="string", example="Project Title"),
     *                  @OA\Property(property="is_dar_dialogue", type="boolean", example="false"),
     *                  @OA\Property(property="is_dar_status", type="boolean", example="false"),
     *                  @OA\Property(property="is_feasibility_enquiry", type="boolean", example="false"),
     *                  @OA\Property(property="is_general_enquiry", type="boolean", example="false"),
     *                  @OA\Property(property="is_dar_review", type="boolean", example="false"),
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

        $input = $request->all();
        $jwtUser = $input['jwt_user'] ?? [];
        $user = User::where('id', $jwtUser['id'])->first();

        if (is_null($user)) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User not found for id: ' . $jwtUser['id'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_BAD_REQUEST.message'),
                'data' => null,
            ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }

        try {
            $payload = $this->buildPayload($input, $user);
            $teamConfig = $this->getTeamConfiguration($input, $payload['thread']['datasets']);

            $payload['thread']['dataCustodians'] = $teamConfig['data_custodians'] ?? [];
            $payload['message']['message_body']['[[TEAM_NAME]]'] = array_unique($teamConfig['team_names'] ?? []);

            // For each dataset we need to determine if teams are responsible for the data providing
            // if not, then a separate enquiry thread and message are created for that team also.

            $teamIds = array_unique($teamConfig['team_ids'] ?? []);
            $allThreadIds = [];
            foreach ($teamIds as $teamId) {
                $payload['thread']['unique_key'] = Str::random(8); // 8 chars in length
                $payload['thread']['team_id'] = $teamId;
                $enquiryThreadId = $this->createEnquiryThread($payload['thread']);
                $allThreadIds[] = $enquiryThreadId;
                $this->createEnquiryMessage($enquiryThreadId, $payload['message']);
                $usersToNotify = $this->getUsersByTeamIds([$teamId]);

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
                if ($input['is_feasibility_enquiry'] == true) {
                    $this->sendEmail('feasibilityenquiry.firstmessage', $payload, $usersToNotify, $jwtUser, $payload['thread']['user_preferred_email']);
                } elseif ($input['is_general_enquiry'] == true) {
                    $this->sendEmail('generalenquiry.firstmessage', $payload, $usersToNotify, $jwtUser, $payload['thread']['user_preferred_email']);
                } elseif ($input['is_dar_dialogue'] == true) {
                    $this->sendEmail('dar.firstmessage', $payload, $usersToNotify, $jwtUser, $payload['thread']['user_preferred_email']);
                }

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'EnquiryThread ' . $enquiryThreadId . ' created',
                ]);
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $enquiryThreadId,
                'all_threads' => $allThreadIds,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_BAD_REQUEST.message'),
                'data' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }
    }

    private function buildPayload(array $input, User $user): array
    {
        return [
            'thread' => [
                'user_id' => $user->id,
                'user_preferred_email' => $this->getPreferredEmail($input['from'], $user),
                'team_ids' => [],
                'enquiry_unique_key' => Str::random(8),
                'project_title' => $input['project_title'] ?? "",
                'is_dar_dialogue' => $input['is_dar_dialogue'],
                'is_dar_status' => $input['is_dar_status'],
                'is_feasibility_enquiry' => $input['is_feasibility_enquiry'],
                'is_general_enquiry' => $input['is_general_enquiry'],
                'is_dar_review' => $input['is_dar_review'] ?? false,
                'datasets' => $this->mapDatasets($input['datasets']),
                'enabled' => true,
            ],
            'message' => [
                'from' => $input['from'],
                'message_body' => [
                    '[[TEAM_NAME]]' => [],
                    '[[USER_FIRST_NAME]]' => $user->firstname,
                    '[[USER_LAST_NAME]]' => $user->lastname,
                    '[[USER_ORGANISATION]]' => $user->organisation ?? $input['organisation'],
                    '[[CONTACT_NUMBER]]' => $input['contact_number'] ?? "",
                    '[[PROJECT_TITLE]]' => $input['project_title'] ?? "",
                    '[[RESEARCH_AIM]]' => $input['research_aim'] ?? "",
                    '[[OTHER_DATASETS_YES_NO]]' => $input['other_datasets'] ?? "",
                    '[[DATASETS_PARTS_YES_NO]]' => $input['dataset_parts_known'] ?? "",
                    '[[FUNDING]]' => $input['funding'] ?? "",
                    '[[PUBLIC_BENEFIT]]' => $input['potential_research_benefit'] ?? "",
                    '[[QUERY]]' => $input['query'] ?? "",
                    '[[MESSAGE]]' => $input['message'] ?? "",
                    '[[CURRENT_YEAR]]' => date('Y'),
                ],
            ],
        ];
    }

    private function getPreferredEmail(string $from, User $user): string
    {
        if ($from === $user['secondary_email']) {
            return "secondary";
        } else {
            return "primary";
        }
    }

    private function getTeamConfiguration(array $input, array $datasets): array
    {
        $teamIds = [];
        $teamNames = [];
        $dataCustodians = [];

        if (!Feature::active('SDEConciergeServiceEnquiry')) {
            return $this->getStandardTeamConfiguration($datasets);
        }

        [$conciergeId, $conciergeName] = $this->getNetworkConcierge();
        $sdeTeamIds = $this->getSdeTeamIds();
        $multipleDatasets = count($datasets) > 1;





        if ($input['is_general_enquiry']) {
            foreach ($datasets as $dataset) {
                $team = Team::find($dataset['team_id']);
                $teamIds[] = $team->id;
                $teamNames[] = $team->name;

            }
            if (count($teamIds) > 1) {
                // 1/2/3 none sde and 1/2/3 sde - does go to con
                // 1 sde - does not go to con - exact words from Big Stephen
                if ($this->shouldUseConcierge($teamIds, $sdeTeamIds, $multipleDatasets)) {
                    $teamIds[] = $conciergeId;
                    $teamNames[] = $conciergeName;
                }
            }

        } elseif ($input['is_feasibility_enquiry'] || $input['is_dar_dialogue']) {
            // Batch load datasets to avoid N+1 queries
            $datasetIds = collect($datasets)->pluck('dataset_id');
            $datasetsWithMetadata = Dataset::with('latestMetadata')
                ->whereIn('id', $datasetIds)
                ->get()
                ->keyBy('id');

            foreach ($datasets as $dataset) {
                $datasetModel = $datasetsWithMetadata[$dataset['dataset_id']];
                $team = $this->getTeamFromDataset($datasetModel);
                $teamIds[] = $team->id;
                $teamNames[] = $team->name;
            }

            if (count($teamIds) > 1) {
                if ($this->shouldUseConcierge($teamIds, $sdeTeamIds, $multipleDatasets)) {
                    $teamIds[] = $conciergeId;
                    $teamNames[] = $conciergeName;
                }
            }

        }

        return [
            'team_ids' => array_unique($teamIds),
            'team_names' => array_unique($teamNames),
            'data_custodians' => array_unique($dataCustodians),
        ];
    }

    private function getStandardTeamConfiguration(array $datasets): array
    {
        $teamIds = collect($datasets)->pluck('team_id')->unique();
        $teams = Team::whereIn('id', $teamIds)->get()->keyBy('id');

        return [
            'team_ids' => $teamIds->toArray(),
            'team_names' => $teams->pluck('name')->toArray(),
            'data_custodians' => $teams->pluck('name')->toArray(),
        ];
    }

    private function getSdeTeamIds(): array
    {
        $sdeNetwork = DataProviderColl::where('name', 'LIKE', '%SDE%')
            ->with('teams')
            ->first();

        return $sdeNetwork ? $sdeNetwork->teams->pluck('id')->toArray() : [];
    }

    private function shouldUseConcierge(array $teamIds, array $sdeTeamIds, bool $multipleDatasets): bool
    {
        return !empty(array_intersect($teamIds, $sdeTeamIds)) && $multipleDatasets;
    }

    private function getTeamFromDataset($dataset): Team
    {
        $metadata = $dataset->latestMetadata;
        $gatewayId = $metadata->metadata['metadata']['summary']['publisher']['gatewayId'];

        return is_numeric($gatewayId)
            ? Team::find($gatewayId)
            : Team::where('pid', $gatewayId)->first();
    }

    private function getNetworkConcierge(): array
    {
        $team = Team::where('name', 'LIKE', '%SDE Network%')->first();
        if ($team) {
            return array($team->id, $team->name);
        }
        return array(null, null);
    }
    private function mapDatasets(array $datasets): array
    {
        $arr = [];

        foreach ($datasets as $dataset) {
            // Handles the case where the enquiry is about no datasets, only to a team
            if ($dataset['dataset_id'] === null) {
                $arr[] = [
                    'title' => null,
                    'dataset_id' => null,
                    'url' => null,
                    'interest_type' => $dataset['interest_type'],
                    'team_id' => $dataset['team_id'],
                ];
            } else {
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
        }

        return $arr;
    }
}
