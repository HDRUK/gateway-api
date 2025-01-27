<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\QuestionBank;
use App\Models\QuestionBankVersion;
use App\Models\QuestionBankVersionHasChildVersion;
use App\Models\QuestionHasTeam;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionBank\EditQuestionBank;
use App\Http\Requests\QuestionBank\CreateQuestionBank;
use App\Http\Requests\QuestionBank\DeleteQuestionBank;
use App\Http\Requests\QuestionBank\UpdateQuestionBank;
use App\Http\Requests\QuestionBank\GetQuestionBankVersion;
use App\Http\Requests\QuestionBank\UpdateStatusQuestionBank;
use App\Http\Traits\QuestionBankHelpers;
use App\Http\Traits\RequestTransformation;

class QuestionBankController extends Controller
{
    use RequestTransformation;
    use QuestionBankHelpers;

    /**
     * @OA\Get(
     *      path="/api/v1/questions",
     *      summary="List of question bank questions",
     *      description="List of question bank questions",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="section_id",
     *          in="query",
     *          description="section id",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="section id",
     *          ),
     *      ),
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
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="page",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="page",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example="1"),
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
     *                      @OA\Property(property="title", type="string", example="This is a question"),
     *                      @OA\Property(property="guidance", type="string", example="This is a question's guidance"),
     *                      @OA\Property(property="options", type="array", @OA\Items()),
     *                      @OA\Property(property="component", type="string", example="RadioGroup"),
     *                      @OA\Property(property="validations", type="array", @OA\Items()),),
     *                      @OA\Property(property="version_id", type="integer", example="123"),
     *                  )
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http://localhost:8000/api/v1/questions?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http://localhost:8000/api/v1/questions?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http://localhost:8000/api/v1/questions"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $sectionId = $input['section_id'] ?? null;
            $isChild = $input['is_child'] ?? null;
            $perPage = request('per_page', Config::get('constants.per_page'));

            $questions = QuestionBank::with(
                ['latestVersion', 'latestVersion.childVersions']
            )->where('archived', false)
            ->when(
                $sectionId,
                function ($query) use ($sectionId) {
                    return $query->where('section_id', '=', $sectionId);
                }
            )
            ->when(
                !is_null($isChild),
                function ($query) use ($isChild) {
                    return $query->where('is_child', '=', $isChild);
                }
            )
            ->paginate(
                function ($total) use ($perPage) {
                    if($perPage === -1) {
                        return $total;
                    }
                    return $perPage;
                },
                ['*'],
                'page'
            );

            $questions->transform(function ($question) {
                $question = $this->getVersion($question);
                return $question;
            });

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank get all',
            ]);

            return response()->json(
                $questions
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
     *      path="/api/v1/questions/standard",
     *      summary="List of standard question bank questions",
     *      description="List of standard question bank questions",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@indexStandard",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="section_id",
     *          in="query",
     *          description="section id",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="section id",
     *          ),
     *      ),
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
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="page",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="page",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example="1"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="section_id", type="integer", example="1"),
     *                      @OA\Property(property="user_id", type="integer", example="1"),
     *                      @OA\Property(property="locked", type="boolean", example="false"),
     *                      @OA\Property(property="archived", type="boolean", example="false"),
     *                      @OA\Property(property="archived_date", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="force_required", type="boolean", example="false"),
     *                      @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *                      @OA\Property(property="is_child", type="boolean", example="true"),
     *                      @OA\Property(property="question_type", type="string", example="STANDARD"),
     *                      @OA\Property(property="title", type="string", example="This is a question"),
     *                      @OA\Property(property="guidance", type="string", example="This is a question's guidance"),
     *                      @OA\Property(property="options", type="array", @OA\Items()),
     *                      @OA\Property(property="component", type="string", example="RadioGroup"),
     *                      @OA\Property(property="validations", type="array", @OA\Items()),),
     *                      @OA\Property(property="version_id", type="integer", example="123"),
     *                  )
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http://localhost:8000/api/v1/questions?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http://localhost:8000/api/v1/questions?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http://localhost:8000/api/v1/questions"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function indexStandard(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $sectionId = $input['section_id'] ?? null;
            $isChild = $input['is_child'] ?? null;
            $perPage = request('per_page', Config::get('constants.per_page'));

            $questions = QuestionBank::with([
                'latestVersion', 'latestVersion.childVersions'
            ])->where('question_type', QuestionBank::STANDARD_TYPE)
            ->where('archived', false)
            ->when(
                $sectionId,
                function ($query) use ($sectionId) {
                    return $query->where('section_id', '=', $sectionId);
                }
            )
            ->when(
                !is_null($isChild),
                function ($query) use ($isChild) {
                    return $query->where('is_child', '=', $isChild);
                }
            )
            ->paginate(
                function ($total) use ($perPage) {
                    if($perPage === -1) {
                        return $total;
                    }
                    return $perPage;
                },
                ['*'],
                'page'
            );

            $questions->transform(function ($question) {
                $question = $this->getVersion($question);
                return $question;
            });

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank get all standard',
            ]);

            return response()->json(
                $questions
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
     *      path="/api/v1/questions/custom",
     *      summary="List of custom question bank questions",
     *      description="List of custom question bank questions",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@indexCustom",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="section_id",
     *          in="query",
     *          description="section id",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="section id",
     *          ),
     *      ),
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
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="page",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="page",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example="1"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="section_id", type="integer", example="1"),
     *                      @OA\Property(property="user_id", type="integer", example="1"),
     *                      @OA\Property(property="locked", type="boolean", example="false"),
     *                      @OA\Property(property="archived", type="boolean", example="false"),
     *                      @OA\Property(property="archived_date", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="force_required", type="boolean", example="false"),
     *                      @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *                      @OA\Property(property="is_child", type="boolean", example="true"),
     *                      @OA\Property(property="question_type", type="string", example="CUSTOM"),
     *                      @OA\Property(property="title", type="string", example="This is a question"),
     *                      @OA\Property(property="guidance", type="string", example="This is a question's guidance"),
     *                      @OA\Property(property="options", type="array", @OA\Items()),
     *                      @OA\Property(property="component", type="string", example="RadioGroup"),
     *                      @OA\Property(property="validations", type="array", @OA\Items()),),
     *                      @OA\Property(property="version_id", type="integer", example="123"),
     *                  )
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http://localhost:8000/api/v1/questions?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http://localhost:8000/api/v1/questions?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http://localhost:8000/api/v1/questions"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function indexCustom(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $sectionId = $input['section_id'] ?? null;
            $isChild = $input['is_child'] ?? null;
            $perPage = request('per_page', Config::get('constants.per_page'));

            $questions = QuestionBank::with([
                'latestVersion', 'latestVersion.childVersions'
            ])->where('question_type', QuestionBank::CUSTOM_TYPE)
            ->where('archived', false)
            ->when(
                $sectionId,
                function ($query) use ($sectionId) {
                    return $query->where('section_id', '=', $sectionId);
                }
            )
            ->when(
                !is_null($isChild),
                function ($query) use ($isChild) {
                    return $query->where('is_child', '=', $isChild);
                }
            )
            ->paginate(
                function ($total) use ($perPage) {
                    if($perPage === -1) {
                        return $total;
                    }
                    return $perPage;
                },
                ['*'],
                'page'
            );

            $questions->transform(function ($question) {
                $question = $this->getVersion($question);
                return $question;
            });

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank get all custom',
            ]);

            return response()->json(
                $questions
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
     *      path="/api/v1/questions/archived",
     *      summary="List of archived question bank questions",
     *      description="List of archived question bank questions",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@indexArchived",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="section_id",
     *          in="query",
     *          description="section id",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="section id",
     *          ),
     *      ),
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
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="page",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="page",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example="1"),
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
     *                      @OA\Property(property="title", type="string", example="This is a question"),
     *                      @OA\Property(property="guidance", type="string", example="This is a question's guidance"),
     *                      @OA\Property(property="options", type="array", @OA\Items()),
     *                      @OA\Property(property="component", type="string", example="RadioGroup"),
     *                      @OA\Property(property="validations", type="array", @OA\Items()),),
     *                      @OA\Property(property="version_id", type="integer", example="123"),
     *                  )
     *              ),
     *              @OA\Property(property="first_page_url", type="string", example="http://localhost:8000/api/v1/questions?page=1"),
     *              @OA\Property(property="from", type="integer", example="1"),
     *              @OA\Property(property="last_page", type="integer", example="1"),
     *              @OA\Property(property="last_page_url", type="string", example="http://localhost:8000/api/v1/questions?page=1"),
     *              @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *              @OA\Property(property="next_page_url", type="string", example="null"),
     *              @OA\Property(property="path", type="string", example="http://localhost:8000/api/v1/questions"),
     *              @OA\Property(property="per_page", type="integer", example="25"),
     *              @OA\Property(property="prev_page_url", type="string", example="null"),
     *              @OA\Property(property="to", type="integer", example="3"),
     *              @OA\Property(property="total", type="integer", example="3"),
     *          )
     *      )
     * )
     */
    public function indexArchived(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $sectionId = $input['section_id'] ?? null;
            $isChild = $input['is_child'] ?? null;
            $perPage = request('per_page', Config::get('constants.per_page'));

            $questions = QuestionBank::with([
                'latestVersion', 'latestVersion.childVersions'
            ])->where('archived', true)
            ->when(
                $sectionId,
                function ($query) use ($sectionId) {
                    return $query->where('section_id', '=', $sectionId);
                }
            )
            ->when(
                !is_null($isChild),
                function ($query) use ($isChild) {
                    return $query->where('is_child', '=', $isChild);
                }
            )
            ->paginate(
                function ($total) use ($perPage) {
                    if($perPage === -1) {
                        return $total;
                    }
                    return $perPage;
                },
                ['*'],
                'page'
            );

            $questions->transform(function ($question) {
                $question = $this->getVersion($question);
                return $question;
            });

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank get all archived',
            ]);

            return response()->json(
                $questions
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
     *      path="/api/v1/questions/{id}",
     *      summary="Return the latest question bank question version for the supplied question id, in an FE-friendly format",
     *      description="Return the latest question bank question version for the supplied question id, in an FE-friendly format",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="question bank question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="question bank question id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="question_id", type="integer", example="1"),
     *                  @OA\Property(property="version", type="integer", example="1"),
     *                  @OA\Property(property="default", type="boolean", example="false"),
     *                  @OA\Property(property="required", type="boolean", example="true"),
     *                  @OA\Property(property="section_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="boolean", example="false"),
     *                  @OA\Property(property="archived", type="boolean", example="false"),
     *                  @OA\Property(property="archived_date", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="force_required", type="boolean", example="false"),
     *                  @OA\Property(property="allow_guidance_override", type="boolean", example="false"),
     *                  @OA\Property(property="is_child", type="integer", example="0"),
     *                  @OA\Property(property="question_type", type="string", example="STANDARD"),
     *                  @OA\Property(property="title", type="string", example="This is a question"),
     *                  @OA\Property(property="guidance", type="string", example="This is a question's guidance"),
     *                  @OA\Property(property="options", type="array", @OA\Items()),
     *                  @OA\Property(property="component", type="string", example="RadioGroup"),
     *                  @OA\Property(property="validations", type="array", @OA\Items()),),
     *                  @OA\Property(property="version_id", type="integer", example="123"),
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
    public function show(GetQuestionBankVersion $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $question = QuestionBank::with([
                'latestVersion',
                'latestVersion.childVersions',
                'teams',
            ])->findOrFail($id);

            if ($question) {
                $questionVersion = $this->getVersion($question);

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'QuestionBank get ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $questionVersion,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
     *      path="/api/v1/questions/version/{id}",
     *      summary="Return a single system question bank question version",
     *      description="Return a single system question bank question version",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@showVersion",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="question bank question version id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="question bank question version id",
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
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="question_id", type="integer", example="1"),
     *                  @OA\Property(property="version", type="integer", example="1"),
     *                  @OA\Property(property="default", type="boolean", example="false"),
     *                  @OA\Property(property="required", type="boolean", example="true"),
     *                  @OA\Property(property="question_json", type="object", example=""),
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
    public function showVersion(GetQuestionBankVersion $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $questionVersion = QuestionBankVersion::findOrFail($id);

            if ($questionVersion) {

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'QuestionBank get ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $questionVersion,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
     * @OA\Post(
     *      path="/api/v1/questions",
     *      summary="Create a new system question bank question with FE-helpful input format",
     *      description="Create a new system question bank question with FE-helpful input format",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="QuestionBank definition",
     *          @OA\JsonContent(
     *              required={"field", "section_id", "guidance", "title", "force_required", "allow_guidance_override", "component", "validations", "options"},
     *              @OA\Property(property="section_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="team_ids", type="array", @OA\Items()),
     *              @OA\Property(property="locked", type="boolean", example="false"),
     *              @OA\Property(property="archived", type="boolean", example="false"),
     *              @OA\Property(property="required", type="boolean", example="false"),
     *              @OA\Property(property="force_required", type="boolean", example="false"),
     *              @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *              @OA\Property(property="default", type="boolean", example="false"),
     *              @OA\Property(property="guidance", type="string", example="Question guidance"),
     *              @OA\Property(property="title", type="string", example="Question title"),
     *              @OA\Property(property="field", type="array", @OA\Items()),
     *              @OA\Property(property="component", type="string", example="RadioGroup"),
     *              @OA\Property(property="validations", type="array", @OA\Items()),
     *              @OA\Property(property="options", type="array", @OA\Items()),
     *              @OA\Property(property="is_child", type="boolean", example="true"),
     *              @OA\Property(property="question_type", type="string", example="STANDARD"),
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
    public function store(CreateQuestionBank $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            if ($input['is_child'] ?? false) {
                return response()->json([
                    'message' => 'Cannot create a child question directly'
                ], 400);
            }

            $question = QuestionBank::create([
                'section_id' => $input['section_id'],
                'user_id' => $input['user_id'] ?? $jwtUser['id'],
                'force_required' => $input['force_required'],
                'allow_guidance_override' => $input['allow_guidance_override'],
                'locked' => $input['locked'] ?? false,
                'archived' => $input['archived'] ?? false,
                'archived_date' => ($input['archived'] ?? false) ? Carbon::now() : null,
                'is_child' => false,
                'question_type' => $input['all_custodians'] ? QuestionBank::STANDARD_TYPE : QuestionBank::CUSTOM_TYPE,
                'team_ids' => $input['all_custodians'] ? [] : $input['team_ids'],
            ]);

            $questionVersion = $this->createVersion($input, $question, 1);

            $this->updateQuestionHasTeams($question, $input);

            $this->handleChildren($questionVersion, $input, 1, $jwtUser);


            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $question->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $question->id,
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
     * @OA\Put(
     *      path="/api/v1/questions/{id}",
     *      summary="Update a system question bank question - children and their version are updated through parents",
     *      description="Update a system question bank question - children and their versions are updated through parents",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="question bank question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="question bank question id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="QuestionBank definition",
     *          @OA\JsonContent(
     *              required={"field", "section_id", "guidance", "title", "force_required", "allow_guidance_override"},
     *              @OA\Property(property="section_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="team_ids", type="array", @OA\Items()),
     *              @OA\Property(property="locked", type="boolean", example="false"),
     *              @OA\Property(property="archived", type="boolean", example="false"),
     *              @OA\Property(property="is_child", type="boolean", example="false"),
     *              @OA\Property(property="question_type", type="string", example="STANDARD"),
     *              @OA\Property(property="required", type="boolean", example="false"),
     *              @OA\Property(property="force_required", type="boolean", example="false"),
     *              @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *              @OA\Property(property="default", type="boolean", example="false"),
     *              @OA\Property(property="guidance", type="string", example="Question guidance"),
     *              @OA\Property(property="title", type="string", example="Question title"),
     *              @OA\Property(property="field", type="array", @OA\Items()),
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
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="section_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="boolean", example="false"),
     *                  @OA\Property(property="archived", type="boolean", example="true"),
     *                  @OA\Property(property="archived_date", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="is_child", type="boolean", example="false"),
     *                  @OA\Property(property="question_type", type="string", example="STANDARD"),
     *                  @OA\Property(property="force_required", type="boolean", example="false"),
     *                  @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *                  @OA\Property(property="options", type="array",
     *                      @OA\Items(type="object",
     *                          @OA\Property(property="label", type="string", example="yes"),
     *                          @OA\Property(property="children", type="array",
     *                              @OA\Items(type="object",
     *                                  @OA\Property(property="label", type="string", example="yes"),
     *                                  @OA\Property(property="field", type="array",
     *                                      @OA\Items(type="object",
     *                                          @OA\Property(property="options", type="array", example="['yes', 'no']", @OA\Items()),
     *                                          @OA\Property(property="component", type="string", example="yes"),
     *                                          @OA\Property(property="validations", type="array", example="[]", @OA\Items()),
     *                                      )
     *                                  ),
     *                                  @OA\Property(property="title", type="string", example="This is my nested question"),
     *                                  @OA\Property(property="guidance", type="string", example="This is how you should answer this nested question"),
     *                                  @OA\Property(property="required", type="boolean", example="false")
     *                              )
     *                          )
     *                      )
     *                  )
     *              )
     *          )
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
    public function update(UpdateQuestionBank $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $question = QuestionBank::findOrFail($id);
            if ($question->is_child) {
                return response()->json([
                    'message' => 'Cannot update a child question directly'
                ], 400);
            }
            if ($input['is_child'] ?? false) {
                return response()->json([
                    'message' => 'Cannot update a question to become a child question'
                ], 400);
            }
            // TODO: handle locking
            $question->update([
                'section_id' => $input['section_id'],
                'user_id' => $input['user_id'] ?? $jwtUser['id'],
                'force_required' => $input['force_required'],
                'allow_guidance_override' => $input['allow_guidance_override'],
                'locked' => $input['locked'] ?? false,
                'archived' => $input['archived'] ?? false,
                'archived_date' => ($input['archived'] ?? false) ? Carbon::now() : null,
                'is_child' => false,
                'question_type' => $input['all_custodians'] ? QuestionBank::STANDARD_TYPE : QuestionBank::CUSTOM_TYPE,
                'team_ids' => $input['all_custodians'] ? [] : $input['team_ids'],
            ]);

            $latestVersion = $question->latestVersion()->first()->version;

            $questionVersion = $this->createVersion($input, $question, $latestVersion + 1);

            $this->updateQuestionHasTeams($question, $input);

            $this->handleChildren($questionVersion, $input, $latestVersion + 1, $jwtUser);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => QuestionBank::where('id', $id)->first(),
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
     * @OA\Patch(
     *      path="/api/v1/questions/{id}",
     *      summary="Edit a system question bank question - use this for parents and children separately",
     *      description="Edit a system question bank question - use this for parents and children separately",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="question bank question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="question bank question id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="QuestionBank definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="section_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="team_ids", type="array", @OA\Items()),
     *              @OA\Property(property="locked", type="boolean", example="false"),
     *              @OA\Property(property="archived", type="boolean", example="false"),
     *              @OA\Property(property="force_required", type="boolean", example="false"),
     *              @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *              @OA\Property(property="question_type", type="string", example="STANDARD"),
     *              @OA\Property(property="default", type="boolean", example="false"),
     *              @OA\Property(property="guidance", type="string", example="Question guidance"),
     *              @OA\Property(property="title", type="string", example="Question title"),
     *              @OA\Property(property="field", type="array", @OA\Items()),
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
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="section_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="boolean", example="false"),
     *                  @OA\Property(property="archived", type="boolean", example="true"),
     *                  @OA\Property(property="archived_date", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="force_required", type="boolean", example="false"),
     *                  @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *                  @OA\Property(property="question_type", type="string", example="STANDARD"),
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
    public function edit(EditQuestionBank $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $question = QuestionBank::findOrFail($id);

            if ($input['is_child'] ?? false) {
                return response()->json([
                    'message' => "Cannot edit a question's 'is_child' field"
                ], 400);
            }

            // TODO: handle locking

            $arrayKeys = [
                'section_id',
                'user_id',
                'force_required',
                'allow_guidance_override',
                'locked',
                'archived',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            if ($array['archived'] ?? false) {
                $array['archived_date'] = Carbon::now();
            }
            if (array_key_exists('all_custodians', $input)) {
                $array['question_type'] = $input['all_custodians'] ? QuestionBank::STANDARD_TYPE : QuestionBank::CUSTOM_TYPE;
            }
            $question->update($array);

            $versionKeys = [
                'field',
                'title',
                'guidance',
                'required',
                'default',
            ];
            $versionArray = $this->checkEditArray($input, $versionKeys);

            if (!empty($versionArray)) {
                $latestVersion = $question->latestVersion()->first();
                $latestJson = $latestVersion->question_json;

                $questionJson = [
                    'field' => $input['field'] ?? $latestJson['field'],
                    'title' => $input['title'] ?? $latestJson['title'],
                    'guidance' => $input['guidance'] ?? $latestJson['guidance'],
                ];
                $questionVersion = QuestionBankVersion::where('id', $latestVersion->id)->first();

                $questionVersion = $questionVersion->update([
                    'question_json' => $questionJson,
                    'required' => $input['required'] ?? $latestVersion->required,
                    'default' => $input['default'] ?? $latestVersion->default,
                    'question_id' => $id,
                    'version' => $latestVersion->version,
                ]);
            }

            QuestionHasTeam::where('qb_question_id', $id)->delete();
            if ($question->question_type === QuestionBank::CUSTOM_TYPE) {
                if ($input['team_ids']) {
                    foreach ($input['team_ids'] as $t) {
                        QuestionHasTeam::create([
                            'qb_question_id' => $question->id,
                            'team_id' => $t,
                        ]);
                    }
                }
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => QuestionBank::where('id', $id)->first(),
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
     * @OA\Patch(
     *      path="/api/v1/questions/{id}/{status}",
     *      summary="Lock, unlock, archive or unarchive a question bank question",
     *      description="Lock, unlock, archive or unarchive a question bank question",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@updateStatus",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="question bank question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="question bank question id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="locking",
     *         in="path",
     *         description="lock or unlock",
     *         required=true,
     *         example="lock",
     *         @OA\Schema(
     *            type="string",
     *            description="lock | unlock | archive | unarchive",
     *         ),
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
     *              @OA\Property(property="message", type="string"),
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
    public function updateStatus(UpdateStatusQuestionBank $request, int $id, string $status): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $question = QuestionBank::where('id', $id)->with('latestVersion.childVersions')->first();

            if ($question['is_child']) {
                return response()->json([
                    'message' => "Cannot update a child question's status, update the parent question."
                ], 400);
            }

            if (in_array($status, ['lock', 'unlock'])) {
                $locked = $status === 'lock' ? true : false;
                $question->update(['locked' => $locked]);
                foreach ($question['latestVersion']['childVersions'] as $v) {
                    QuestionBank::where('id', $v['question_id'])->update(['locked' => $locked]);
                }
            } elseif (in_array($status, ['archive', 'unarchive'])) {
                $archived = $status === 'archive' ? true : false;
                $question->update(['archived' => $archived]);
                foreach ($question['latestVersion']['childVersions'] as $v) {
                    QuestionBank::where('id', $v['question_id'])->update(['archived' => $archived]);
                }
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $id . ' status updated',
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
     *      path="/api/v1/questions/{id}",
     *      summary="Delete a system question bank question",
     *      description="Delete a system question bank question",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="question bank question id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="question bank question id",
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
    public function destroy(DeleteQuestionBank $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $question = QuestionBank::findOrFail($id);
            if ($question->is_child) {
                return response()->json([
                    'message' => 'Cannot delete a child question directly'
                ], 400);
            }

            // TODO: handle locking?

            // For each version of this question, check its children.
            // - Delete all versions of all child question versions and their associated questions,
            //   and their QuestionBankVersionHasChildVersion relationship entries,
            //   along with the QuestionHasTeam entries
            //
            // Then delete each version of the question being requested, then the question
            //   itself, and its associated QuestionHasTeam entries
            $questionVersions = $question->versions()->get();

            foreach ($questionVersions as $version) {
                // delete each version's child question versions and their associated QuestionBank and QuestionHasTeam entries
                $childVersions = $version->childVersions;
                foreach ($childVersions as $childVersion) {
                    // Delete association of child version's question to teams
                    QuestionHasTeam::where('qb_question_id', $childVersion->question_id)->delete();
                    // Delete child version's question's versions
                    QuestionBankVersion::where('id', $childVersion->id)->delete();
                    // Delete child version's question
                    QuestionBank::where('id', $childVersion->question_id)->delete();
                }
                // delete parent-child records from relationship table
                QuestionBankVersionHasChildVersion::where('parent_qbv_id', $version->id)->delete();
                // delete each version
                QuestionBankVersion::where('id', $version->id)->delete();

            };
            // delete the requested question
            QuestionBank::where('id', $id)->delete();
            // delete QuestionHasTeam entries for the requested question
            QuestionHasTeam::where('qb_question_id', $id)->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $id . ' deleted',
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

    private function createVersion($input, $question, $version)
    {
        $questionVersion = QuestionBankVersion::create([
            'question_json' => [
                'field' => [
                    'component' => $input['component'],
                    'validations' => $input['validations'],
                    'options' => array_column($input['options'], 'label'),
                ],
                'title' => $input['title'],
                'guidance' => $input['guidance'],
            ],
            'required' => $input['required'] ?? false,
            'default' => $input['default'],
            'question_id' => $question->id,
            'version' => $version,
        ]);

        return $questionVersion;
    }

    private function handleChildren(QuestionBankVersion $questionVersion, array $input, int $versionNumber, array $jwtUser)
    {
        // Don't allow children to also have children, and only allow certain parent types to have children
        if (!($input['is_child'] ?? false)
        && isset($input['options'])
        && in_array($input['component'], ['RadioGroup', 'CheckboxGroup', 'Autocomplete'])) {
            // Create all children questions and question versions as required.
            // All must by design have the same version number as the parent - parents and children move versions in lockstep
            if (isset($input['options'])) {
                foreach ($input['options'] as $option) {
                    $label = $option['label'];
                    $children = $option['children'];

                    foreach ($children as $child) {
                        $childQuestion = QuestionBank::create([
                            'section_id' => $input['section_id'],
                            'user_id' => $input['user_id'] ?? $jwtUser['id'],
                            'force_required' => $child['force_required'],
                            'allow_guidance_override' => $child['allow_guidance_override'],
                            'locked' => $child['locked'] ?? false,
                            'archived' => $child['archived'] ?? false,
                            'archived_date' => ($child['archived'] ?? false) ? Carbon::now() : null,
                            'is_child' => true,
                            'question_type' => $input['all_custodians'] ? QuestionBank::STANDARD_TYPE : QuestionBank::CUSTOM_TYPE,
                        ]);

                        $field = [
                            'component' => $child['component'],
                            'validations' => $child['validations'],
                            'options' => array_column($child['options'], 'label'),
                        ];

                        $questionJson = [
                            'field' => $field,
                            'title' => $child['title'],
                            'guidance' => $child['guidance'],
                        ];

                        $childQuestionVersion = QuestionBankVersion::create([
                            'question_json' => $questionJson,
                            'required' => $child['required'] ?? false,
                            'default' => $input['default'],
                            'question_id' => $childQuestion->id,
                            'version' =>  $versionNumber,
                        ]);

                        $questionHasChild = QuestionBankVersionHasChildVersion::create([
                            'parent_qbv_id' => $questionVersion->id,
                            'child_qbv_id' => $childQuestionVersion->id,
                            'condition' => $label,
                        ]);

                        $this->updateQuestionHasTeams($childQuestion, $input);
                    }
                }
            }
        }
    }

    private function updateQuestionHasTeams(QuestionBank $question, array $input)
    {
        QuestionHasTeam::where('qb_question_id', $question->id)->delete();
        if ($question->question_type === QuestionBank::CUSTOM_TYPE) {
            if (isset($input['team_ids']) && $input['team_ids']) {
                foreach ($input['team_ids'] as $t) {
                    QuestionHasTeam::create([
                        'qb_question_id' => $question->id,
                        'team_id' => $t,
                    ]);
                }
            }
        }
    }
}
