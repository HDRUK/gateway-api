<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Exception;
use App\Models\QuestionBank;
use App\Models\QuestionHasTeam;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionBank\GetQuestionBankSection;
use App\Http\Traits\RequestTransformation;

class TeamQuestionBankController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/questions/section/{sectionId}",
     *      summary="List of question bank questions by section",
     *      description="List of question bank questions by section",
     *      tags={"QuestionBank"},
     *      summary="TeamQuestionBank@indexBySection",
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
     *                      @OA\Property(property="section_id", type="integer", example="1"),
     *                      @OA\Property(property="user_id", type="integer", example="1"),
     *                      @OA\Property(property="locked", type="boolean", example="false"),
     *                      @OA\Property(property="archived", type="boolean", example="true"),
     *                      @OA\Property(property="archived_date", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="force_required", type="boolean", example="false"),
     *                      @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *                      @OA\Property(property="is_child", type="boolean", example="true"),
     *                      @OA\Property(property="question_type", type="string", example="STANDARD"),
     *                      @OA\Property(property="latest_version", type="object", example=""),
     *                      @OA\Property(property="versions", type="object", example=""),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function indexBySection(GetQuestionBankSection $request, int $teamId, int $sectionId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $teamQuestions = QuestionHasTeam::where('team_id', $teamId)
                ->select('qb_question_id')
                ->pluck('qb_question_id');

            $questionsCustom = QuestionBank::with([
                'latestVersion', 'versions', 'versions.childVersions'
            ])->where('archived', false)
            ->whereIn('id', $teamQuestions)
            ->where('section_id', $sectionId)
            ->get()
            ->toArray();


            $questionsStandard = QuestionBank::with([
                'latestVersion', 'versions', 'versions.childVersions'
            ])->where('archived', false)
            ->where('question_type', 'STANDARD')
            ->where('section_id', $sectionId)
            ->get()
            ->toArray();

            $questions = array_merge($questionsCustom, $questionsStandard);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank get all by section',
            ]);

            return response()->json([
                'data' => $questions
            ]);
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
