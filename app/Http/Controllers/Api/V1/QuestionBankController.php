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
use App\Http\Requests\QuestionBank\GetQuestionBank;
use App\Http\Requests\QuestionBank\GetQuestionBankSection;
use App\Http\Requests\QuestionBank\GetQuestionBankVersion;
use App\Http\Requests\QuestionBank\EditQuestionBank;
use App\Http\Requests\QuestionBank\CreateQuestionBank;
use App\Http\Requests\QuestionBank\DeleteQuestionBank;
use App\Http\Requests\QuestionBank\LockingQuestionBank;
use App\Http\Requests\QuestionBank\UpdateQuestionBank;
use App\Http\Traits\RequestTransformation;

class QuestionBankController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/questions",
     *      summary="List of question bank questions",
     *      description="List of question bank questions",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@index",
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
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $questions = QuestionBank::with(
                ['latestVersion', 'versions', 'versions.childVersions']
            )->where('archived', false)
            ->paginate(
                Config::get('constants.per_page'),
                ['*'],
                'page'
            );

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
     *      path="/api/v1/questions/archived",
     *      summary="List of archived question bank questions",
     *      description="List of archived question bank questions",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@indexArchived",
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
    public function indexArchived(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $questions = QuestionBank::with([
                'latestVersion', 'versions', 'versions.childVersions'
            ])->where('archived', true)
                ->paginate(
                    Config::get('constants.per_page'),
                    ['*'],
                    'page'
                );

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
     *      path="/api/v1/questions/section/{sectionId}",
     *      summary="List of question bank questions by section",
     *      description="List of question bank questions by section",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@indexBySection",
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
    public function indexBySection(GetQuestionBankSection $request, int $sectionId): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $questions = QuestionBank::with([
                'latestVersion', 'versions', 'versions.childVersions'
            ])->where('archived', false)
            ->where('section_id', $sectionId)
            ->paginate(
                Config::get('constants.per_page'),
                ['*'],
                'page'
            );

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank get all by section',
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
     *      summary="Return a single system question bank question",
     *      description="Return a single system question bank question",
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
     *      @OA\Parameter(
     *         name="with_versions",
     *         in="query",
     *         description="include all versions",
     *         required=false,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="include all versions flag",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="with_section",
     *         in="query",
     *         description="include section information",
     *         required=false,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="include section information flag",
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
     *                  @OA\Property(property="section_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="boolean", example="false"),
     *                  @OA\Property(property="archived", type="boolean", example="true"),
     *                  @OA\Property(property="archived_date", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="force_required", type="boolean", example="false"),
     *                  @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *                  @OA\Property(property="is_child", type="boolean", example="true"),
     *                  @OA\Property(property="question_type", type="string", example="STANDARD"),
     *                  @OA\Property(property="latest_version", type="object", example=""),
     *                  @OA\Property(property="versions", type="object", example=""),
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
    public function show(GetQuestionBank $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $withVersions = $request->boolean('with_versions', true);
            $withSection = $request->boolean('with_section', true);
            $withFields = [
                'latestVersion',
                'latestVersion.childVersions',
            ];
            if ($withVersions) {
                $withFields = array_merge($withFields, ['versions', 'versions.childVersions']);
            }
            if ($withSection) {
                $withFields = array_merge($withFields, ['section']);
            }

            $question = QuestionBank::with($withFields)->findOrFail($id);

            if ($question) {

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'QuestionBank get ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $question,
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
     *      summary="Create a new system question bank question",
     *      description="Creates a new system question bank question",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="QuestionBank definition",
     *          @OA\JsonContent(
     *              required={"field", "section_id", "required", "guidance", "title", "force_required", "allow_guidance_override"},
     *              @OA\Property(property="section_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="team_id", type="array", @OA\Items()),
     *              @OA\Property(property="locked", type="boolean", example="false"),
     *              @OA\Property(property="archived", type="boolean", example="false"),
     *              @OA\Property(property="force_required", type="boolean", example="false"),
     *              @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *              @OA\Property(property="default", type="integer", example="1"),
     *              @OA\Property(property="guidance", type="string", example="Question guidance"),
     *              @OA\Property(property="title", type="string", example="Question title"),
     *              @OA\Property(property="field", type="array", @OA\Items()),
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
                'question_type' => $input['question_type'] ?? QuestionBank::STANDARD_TYPE,
            ]);

            $questionJson = [
                'field' => $input['field'],
                'title' => $input['title'],
                'guidance' => $input['guidance'],
                'required' => $input['required'],
            ];

            $questionVersion = QuestionBankVersion::create([
                'question_json' => json_encode($questionJson),
                'required' => $input['required'],
                'default' => $input['default'],
                'question_id' => $question->id,
                'version' => 1,
            ]);

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
     *      summary="Update a system question bank question - children are updated through parents",
     *      description="Update a system question bank question - children are updated through parents",
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
     *              required={"field", "section_id", "required", "guidance", "title", "force_required", "allow_guidance_override"},
     *              @OA\Property(property="section_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="team_id", type="array", @OA\Items()),
     *              @OA\Property(property="locked", type="boolean", example="false"),
     *              @OA\Property(property="archived", type="boolean", example="false"),
     *              @OA\Property(property="is_child", type="boolean", example="false"),
     *              @OA\Property(property="question_type", type="string", example="STANDARD"),
     *              @OA\Property(property="force_required", type="boolean", example="false"),
     *              @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *              @OA\Property(property="default", type="integer", example="1"),
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
                'question_type' => $input['question_type'] ?? 'STANDARD',
            ]);

            $questionJson = [
                'field' => $input['field'],
                'title' => $input['title'],
                'guidance' => $input['guidance'],
                'required' => $input['required'],
            ];

            $latestVersion = $question->latestVersion()->first();

            $questionVersion = QuestionBankVersion::create([
                'question_json' => json_encode($questionJson),
                'required' => $input['required'],
                'default' => $input['default'],
                'question_id' => $question->id,
                'version' => $latestVersion->version + 1,
            ]);


            $this->updateQuestionHasTeams($question, $input);

            $this->handleChildren($questionVersion, $input, $latestVersion->version + 1, $jwtUser);

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
     *              @OA\Property(property="team_id", type="array", @OA\Items()),
     *              @OA\Property(property="locked", type="boolean", example="false"),
     *              @OA\Property(property="archived", type="boolean", example="false"),
     *              @OA\Property(property="force_required", type="boolean", example="false"),
     *              @OA\Property(property="allow_guidance_override", type="boolean", example="true"),
     *              @OA\Property(property="question_type", type="string", example="STANDARD"),
     *              @OA\Property(property="default", type="integer", example="1"),
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
                'question_type',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            if ($array['archived'] ?? false) {
                $array['archived_date'] = Carbon::now();
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
                $latestJson = json_decode($latestVersion->question_json, true);

                $questionJson = [
                    'field' => $input['field'] ?? $latestJson['field'],
                    'title' => $input['title'] ?? $latestJson['title'],
                    'guidance' => $input['guidance'] ?? $latestJson['guidance'],
                    'required' => $input['required'] ?? $latestJson['required'],
                ];
                $questionVersion = QuestionBankVersion::where('id', $latestVersion->id)->first();

                $questionVersion = $questionVersion->update([
                    'question_json' => json_encode($questionJson),
                    'required' => $input['required'] ?? $latestVersion->required,
                    'default' => $input['default'] ?? $latestVersion->default,
                    'question_id' => $id,
                    'version' => $latestVersion->version,
                ]);
            }

            if ($question->question_type === QuestionBank::CUSTOM_TYPE) {
                QuestionHasTeam::where('qb_question_id', $id)->delete();
                if ($input['team_id']) {
                    foreach ($input['team_id'] as $t) {
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
     *      path="/api/v1/questions/{id}/{locking}",
     *      summary="Lock or unlock a question bank question",
     *      description="Lock or unlock a question bank question",
     *      tags={"QuestionBank"},
     *      summary="QuestionBank@locking",
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
     *            description="lock | unlock",
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
    public function locking(LockingQuestionBank $request, int $id, string $locking): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $question = QuestionBank::findOrFail($id)->with('latestVersion.childVersions')->get();

            $locked = $locking === 'lock' ? true : false;
            $question->update(['locked' => $locked]);

            // lock children too
            foreach ($question['latest_version']['child_versions'] as $v) {
                QuestionBank::where('id', $v['question_id'])->update(['locked' => $locked]);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $id . ' updated',
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

    private function handleChildren(QuestionBankVersion $questionVersion, array $input, int $versionNumber, array $jwtUser)
    {
        // Don't allow children to also have children, and only allow certain parent types to have children
        if (!($input['is_child'] ?? false)
        && isset($input['children'])
        && in_array($input['field']['component'], ['RadioGroup', 'CheckboxGroup', 'Autocomplete'])) {
            // Create all children questions and question versions as required.
            // All must by design have the same version number as the parent - parents and children move versions in lockstep
            if (isset($input['children'])) {
                foreach ($input['children'] as $childListCondition => $childList) {
                    foreach ($childList as $child) {
                        $childQuestion = QuestionBank::create([
                            'section_id' => $input['section_id'],
                            'user_id' => $input['user_id'] ?? $jwtUser['id'],
                            'force_required' => $child['force_required'],
                            'allow_guidance_override' => $child['allow_guidance_override'],
                            'locked' => $child['locked'] ?? false,
                            'archived' => $child['archived'] ?? false,
                            'archived_date' => ($child['archived'] ?? false) ? Carbon::now() : null,
                            'is_child' => true,
                        ]);

                        $questionJson = [
                            'field' => $child['field'],
                            'title' => $child['title'],
                            'guidance' => $child['guidance'],
                            'required' => $child['required'],
                        ];

                        $childQuestionVersion = QuestionBankVersion::create([
                            'question_json' => json_encode($questionJson),
                            'required' => $child['required'],
                            'default' => $child['default'],
                            'question_id' => $childQuestion->id,
                            'version' =>  $versionNumber,
                        ]);

                        $questionHasChild = QuestionBankVersionHasChildVersion::create([
                            'parent_qbv_id' => $questionVersion->id,
                            'child_qbv_id' => $childQuestionVersion->id,
                            'condition' => $childListCondition,
                        ]);

                        $this->updateQuestionHasTeams($childQuestion, $input);
                    }
                }
            }
        }
    }

    private function updateQuestionHasTeams(QuestionBank $question, array $input)
    {
        if ($question->question_type === QuestionBank::CUSTOM_TYPE) {
            QuestionHasTeam::where('qb_question_id', $question->id)->delete();
            if (isset($input['team_id']) && $input['team_id']) {
                foreach ($input['team_id'] as $t) {
                    QuestionHasTeam::create([
                        'qb_question_id' => $question->id,
                        'team_id' => $t,
                    ]);
                }
            }
        }
    }
}
