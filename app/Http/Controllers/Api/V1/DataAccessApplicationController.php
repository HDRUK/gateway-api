<?php

namespace App\Http\Controllers\Api\V1;

use CloudLogger;
use Config;
use Auditor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Traits\DataAccessApplicationHelpers;
use App\Http\Traits\QuestionBankHelpers;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\DataAccessApplication\CreateDataAccessApplication;
use App\Http\Requests\DataAccessApplication\DeleteDataAccessApplication;
use App\Http\Requests\DataAccessApplication\DeleteDataAccessApplicationFile;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationHasDataset;
use App\Models\DataAccessApplicationHasQuestion;
use App\Models\DataAccessTemplate;
use App\Models\Dataset;
use App\Models\Team;
use App\Models\TeamHasDataAccessApplication;
use App\Models\Upload;
use App\Exceptions\UnauthorizedException;

class DataAccessApplicationController extends Controller
{
    use RequestTransformation;
    use DataAccessApplicationHelpers;
    use QuestionBankHelpers;

    /**
     * @OA\Post(
     *      path="/api/v1/dar/applications",
     *      summary="Create a new DAR application",
     *      description="Creates a new DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessApplication definition",
     *          @OA\JsonContent(
     *              required={},
     *              @OA\Property(property="applicant_id", type="integer", example="1"),
     *              @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *              @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *              @OA\Property(property="project_title", type="string", example="A DAR project"),
     *              @OA\Property(property="team_ids", type="array", @OA\Items()),
     *              @OA\Property(property="dataset_ids", type="array", @OA\Items()),
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
    public function store(CreateDataAccessApplication $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::create([
                'applicant_id' => isset($input['applicant_id']) ? $input['applicant_id'] : $jwtUser['id'],
                'project_title' => $input['project_title'],
            ]);

            $projectId = $input['project_id'] ?? $application->id;
            $isJoint = count($input['dataset_ids']) > 1;
            $application->update([
                'project_id' => $projectId,
                'is_joint' => $isJoint,
            ]);

            // find data provider for each dataset
            $teams = array();
            foreach ($input['dataset_ids'] as $d) {
                $metadata = Dataset::findOrFail($d)->lastMetadata();
                DataAccessApplicationHasDataset::create([
                    'dataset_id' => $d,
                    'dar_application_id' => $application->id
                ]);

                $gatewayId = $metadata['metadata']['summary']['publisher']['gatewayId'];
                // check for primary key or pid match...
                if (is_numeric($gatewayId)) {
                    $team = Team::where('id', $gatewayId)->first();
                } else {
                    $team = Team::where('pid', $gatewayId)->first();
                }
                if (!$team) {
                    CloudLogger::write([
                        'action_type' => 'CREATE',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => 'Unable to create data access application for dataset with id ' . $d . ', no matching team found.',
                    ]);
                    continue;
                }

                $teamHasDar = TeamHasDataAccessApplication::where([
                    'dar_application_id' => $application->id,
                    'team_id' => $team->id,
                ])->first();

                if (!is_null($teamHasDar)) {
                    continue;
                }

                TeamHasDataAccessApplication::create([
                    'dar_application_id' => $application->id,
                    'team_id' => $team->id,
                ]);

                $teams[] = $team;
            }

            // compile questions from each teams template
            $questions = array();
            foreach ($teams as $team) {
                $template = DataAccessTemplate::where([
                    'team_id' => $team->id,
                    'published' => true,
                    'locked' => false
                ])->first();
                if ($template) {
                    $application->application_type = $template->template_type;
                    $application->save();
                    $templateQuestions = $template->questions()->get();
                    foreach ($templateQuestions as $q) {
                        $q['team'] = $team->name;
                        if (!isset($questions[$q->question_id])) {
                            $questions[$q->question_id] = [$q];
                        } else {
                            $questions[$q->question_id][] = $q;
                        }
                    }
                }
            }

            // merge the templates including merging of guidance
            $order = 1;
            foreach ($questions as $qId => $question) {
                $required = in_array(true, $question) ? true : false;
                $teams = implode(',', array_column($question, 'team'));

                $guidanceArray = array();
                foreach ($question as $q) {
                    if (isset($guidanceArray[$q['guidance']])) {
                        $guidanceArray[$q['guidance']][] = $q['team'];
                    } else {
                        $guidanceArray[$q['guidance']] = [$q['team']];
                    }
                }
                $guidance = '';
                foreach ($guidanceArray as $g => $t) {
                    $guidance .= '<b>' . implode(',', $t) . '</b>' . '<p><em>' . $g . '</em><p/>';
                }

                DataAccessApplicationHasQuestion::create([
                    'application_id' => $application->id,
                    'question_id' => $qId,
                    'guidance' => $guidance,
                    'required' => $required,
                    'order' => $order,
                    'teams' => $teams
                ]);
                $order += 1;
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $application->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $application->id,
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
     * @OA\Delete(
     *      path="/api/v1/dar/applications/{id}",
     *      summary="Delete a system DAR application",
     *      description="Delete a system DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
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
    public function destroy(DeleteDataAccessApplication $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::findOrFail($id);
            $application->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' deleted',
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

    /**
     * @OA\Delete(
     *      path="/api/v1/dar/applications/{id}/files/{fileId}",
     *      summary="Delete a file associated with a DAR application",
     *      description="Delete a file associated with a DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@destroyFile",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="fileId",
     *         in="path",
     *         description="File id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="string",
     *            description="File uuid",
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
    public function destroyFile(DeleteDataAccessApplicationFile $request, int $id, string $fileuuid): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $upload = Upload::where('uuid', $fileuuid)->first();

            if ($jwtUser['id'] !== $upload->user_id) {
                throw new UnauthorizedException("File does not belong to user");
            }

            Storage::disk(config('gateway.scanning_filesystem_disk', 'local_scan') . '_scanned')
                ->delete($upload->file_location);

            $upload->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' file ' . $fileuuid . ' deleted',
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
