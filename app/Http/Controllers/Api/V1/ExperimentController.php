<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\Experiment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Experiments\ExperimentService;
use App\Http\Requests\Experiment\GetExperiment;
use App\Http\Requests\Experiment\CreateExperiment;
use App\Http\Requests\Experiment\DeleteExperiment;
use App\Http\Requests\Experiment\UpdateExperiment;
use App\Http\Requests\Experiment\CreateExperimentResponse;

class ExperimentController extends Controller
{
    public function __construct(
        private readonly ExperimentService $experimentService,
    ) {
    }

    /**
     * @OA\Get(
     *    path="/api/v1/experiments",
     *    operationId="fetch_all_experiments",
     *    tags={"Experiments"},
     *    summary="ExperimentController@index",
     *    description="Get all experiments with their response counts",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Experiment")),
     *       ),
     *    ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $jwtUser = $request->jwtUser();

        try {
            $experiments = Experiment::query()
                ->orderByDesc('updated_at')
                ->get();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Experiment get all',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $experiments,
            ]);
        } catch (Exception $e) {
            $this->logException($e, $jwtUser, __FUNCTION__);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/experiments/{id}",
     *    operationId="fetch_experiment",
     *    tags={"Experiments"},
     *    summary="ExperimentController@show",
     *    description="Get an experiment by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", ref="#/components/schemas/Experiment"),
     *       ),
     *    ),
     *    @OA\Response(response="404", description="Not found response")
     * )
     */
    public function show(GetExperiment $request, int $id): JsonResponse
    {
        $jwtUser = $request->jwtUser();

        try {
            $experiment = Experiment::findOrFail($id);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Experiment get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $experiment,
            ]);
        } catch (Exception $e) {
            $this->logException($e, $jwtUser, __FUNCTION__);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/experiments",
     *    operationId="create_experiment",
     *    tags={"Experiments"},
     *    summary="ExperimentController@store",
     *    description="Create an experiment",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExperimentInput")),
     *    @OA\Response(
     *       response="201",
     *       description="Created",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="created"),
     *          @OA\Property(property="data", type="integer", example=1),
     *       ),
     *    ),
     * )
     */
    public function store(CreateExperiment $request): JsonResponse
    {
        $jwtUser = $request->jwtUser();

        try {
            $experiment = $this->experimentService->create($request->experimentData(), (int)$jwtUser['id']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Experiment ' . $experiment->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $experiment->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            $this->logException($e, $jwtUser, __FUNCTION__);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/experiments/{id}",
     *    operationId="update_experiment",
     *    tags={"Experiments"},
     *    summary="ExperimentController@update",
     *    description="Update an experiment",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *    @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExperimentInput")),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", ref="#/components/schemas/Experiment"),
     *       ),
     *    ),
     * )
     */
    public function update(UpdateExperiment $request, int $id): JsonResponse
    {
        $jwtUser = $request->jwtUser();

        try {
            $experiment = $this->experimentService->update($id, $request->experimentData());

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Experiment ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $experiment,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            $this->logException($e, $jwtUser, __FUNCTION__);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/experiments/{id}",
     *    operationId="delete_experiment",
     *    tags={"Experiments"},
     *    summary="ExperimentController@destroy",
     *    description="Delete an experiment",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(@OA\Property(property="message", type="string", example="success")),
     *    ),
     * )
     */
    public function destroy(DeleteExperiment $request, int $id): JsonResponse
    {
        $jwtUser = $request->jwtUser();

        try {
            Experiment::findOrFail($id)->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Experiment ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            $this->logException($e, $jwtUser, __FUNCTION__);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/experiments/active",
     *    operationId="fetch_active_experiments",
     *    tags={"Experiments"},
     *    summary="ExperimentController@active",
     *    description="Experiments running for a signed-out visitor, bucketed by x-request-session-id",
     *    @OA\Parameter(name="x-request-session-id", in="header", required=false, @OA\Schema(type="string")),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ActiveExperiment")),
     *       ),
     *    ),
     * )
     */
    public function active(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'success',
            'data' => $this->experimentService->activeFor($request),
        ]);
    }

    /**
     * @OA\Get(
     *    path="/api/v1/experiments/active/me",
     *    operationId="fetch_active_experiments_for_me",
     *    tags={"Experiments"},
     *    summary="ExperimentController@activeForMe",
     *    description="Experiments running for the signed-in user, including logged_in, roles and user_ids audiences",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ActiveExperiment")),
     *       ),
     *    ),
     *    @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function activeForMe(Request $request): JsonResponse
    {
        return $this->active($request);
    }

    /**
     * @OA\Post(
     *    path="/api/v1/experiments/{id}/responses",
     *    operationId="create_experiment_response",
     *    tags={"Experiments"},
     *    summary="ExperimentController@respond",
     *    description="Record a signed-out visitor's answers. An email is required to opt in to contact.",
     *    @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *    @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExperimentResponseInput")),
     *    @OA\Response(
     *       response="201",
     *       description="Created",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="created"),
     *          @OA\Property(property="data", type="integer", example=1),
     *       ),
     *    ),
     *    @OA\Response(response="400", description="Invalid payload or no answers to known questions"),
     *    @OA\Response(response="404", description="Not running or not shown to this visitor"),
     * )
     */
    public function respond(CreateExperimentResponse $request, int $id): JsonResponse
    {
        $experiment = $this->experimentService->findShownTo($id, $request);
        $answers = $this->experimentService->answersFor($experiment, $request->input('answers', []));
        $userId = $this->experimentService->userId($request);

        try {
            $contact = $request->boolean('contact');

            $this->experimentService->submit(
                $experiment,
                $userId,
                $answers,
                $request->input('path'),
                $contact,
                $request->input('email')
            );

            if ($contact) {
                Auditor::log([
                    'user_id' => $userId,
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Experiment ' . $experiment->id . ' response with contact opt-in',
                ]);
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $experiment->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            $this->logException($e, $request->jwtUser() ?: null, __FUNCTION__);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/experiments/{id}/responses/me",
     *    operationId="create_experiment_response_for_me",
     *    tags={"Experiments"},
     *    summary="ExperimentController@respondForMe",
     *    description="Record the signed-in user's answers. contact: true uses the account's preferred email; any email sent is ignored.",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *    @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExperimentResponseInput")),
     *    @OA\Response(
     *       response="201",
     *       description="Created",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="created"),
     *          @OA\Property(property="data", type="integer", example=1),
     *       ),
     *    ),
     *    @OA\Response(response="400", description="Invalid payload or no answers to known questions"),
     *    @OA\Response(response="401", description="Unauthorized"),
     *    @OA\Response(response="404", description="Not running or not shown to this user"),
     * )
     */
    public function respondForMe(CreateExperimentResponse $request, int $id): JsonResponse
    {
        return $this->respond($request, $id);
    }

    private function logException(Exception $e, ?array $jwtUser, string $action): void
    {
        Auditor::log([
            'user_id' => $jwtUser ? (int)$jwtUser['id'] : null,
            'action_type' => 'EXCEPTION',
            'action_name' => class_basename($this) . '@' . $action,
            'description' => $e->getMessage(),
        ]);
    }
}
