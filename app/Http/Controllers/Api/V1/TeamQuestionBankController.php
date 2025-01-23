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
use App\Http\Traits\QuestionBankHelpers;

class TeamQuestionBankController extends Controller
{
    use RequestTransformation;
    use QuestionBankHelpers;

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/questions/section/{sectionId}",
     *      summary="List of question bank questions by section",
     *      description="List of question bank questions by section",
     *      tags={"QuestionBank"},
     *      summary="TeamQuestionBank@indexBySection",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="is_child",
     *          in="query",
     *          description="filter on is_child field",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="filter on is_child field",
     *          ),
     *      ),
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
            $isChild = $input['is_child'] ?? null;

            $teamQuestions = QuestionHasTeam::where('team_id', $teamId)
                ->select('qb_question_id')
                ->pluck('qb_question_id');

            $query = QuestionBank::with([
                'latestVersion', 'latestVersion.childVersions'
            ])->where('archived', false)
            ->where('section_id', $sectionId)
            ->when(
                !is_null($isChild),
                function ($query) use ($isChild) {
                    return $query->where('is_child', '=', $isChild);
                }
            );

            $questionsCustom = (clone $query)
                ->whereIn('id', $teamQuestions)
                ->get()
                ->toArray();

            $questionsStandard = (clone $query)
                ->where('question_type', 'STANDARD')
                ->get()
                ->toArray();

            $questions = array_merge($questionsCustom, $questionsStandard);

            $questionVersions = [];
            foreach ($questions as $question) {
                $questionVersions[] = $this->getVersion($question);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank get all by section',
            ]);

            return response()->json([
                'data' => $questionVersions
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
