<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use NotFoundException;

use App\Models\DataUseRegister;
use Illuminate\Http\Request;
use App\Http\Requests\CreateDataUseRegisterRequest;
use App\Http\Requests\UpdateDataUseRegisterRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class DataUseRegisterController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/datauseregisters",
     *    operationId="fetch_all_data_use_registers",
     *    tags={"DataUseRegisters"},
     *    summary="DataUseRegisterController@index",
     *    description="Get All Data Use Registers",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(
     *               property="data", 
     *               type="array",
     *               @OA\Items(type="object", 
     *                  @OA\Property(property="id", type="integer", example="1"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="counter", type="integer", example="1"),
     *                  @OA\Property(property="keywords", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="dataset_ids", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="gateway_dataset_ids", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="non_gateway_dataset_ids", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="gateway_applicants", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="non_gateway_applicants", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="funders_and_sponsors", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="other_approval_committees", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="gateway_output_tools", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="gateway_output_papers", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="non_gateway_outputs", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="project_title", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="project_id_text", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="organisation_name", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="organisation_sector", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="lay_summary", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="enabled", type="boolean", example="false"),
     *                  @OA\Property(property="team", type="object", 
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                      @OA\Property(property="name", type="string", example="someName"),
     *                      @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                      @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                      @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                      @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                      @OA\Property(property="is_admin", type="boolean", example="1"),
     *                      @OA\Property(property="member_of", type="string", example="someOrg"),
     *                      @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                      @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                      @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"), 
     *                  ),
     *                  @OA\Property(property="user", type="object", 
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Rocio Mayer"),
     *                     @OA\Property(property="firstname", type="string", example="something or null"),
     *                     @OA\Property(property="lastname", type="string", example="something or null"),
     *                     @OA\Property(property="email", type="string", example="stanton.sibyl@example.net"),
     *                     @OA\Property(property="email_verified_at", type="integer", example="2023-05-18T01:25:00.000000Z"),
     *                     @OA\Property(property="providerid", type="string", example="something or null"),
     *                     @OA\Property(property="provider", type="string", example="something or null"),
     *                     @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                     @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                     @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  ),
     *                  @OA\Property(property="last_activity", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="manual_upload", type="boolean", example="false"),
     *                  @OA\Property(property="rejection_reason", type="string", example="Sit quisquam est recusandae."),
     *               ),
     *            ),
     *         ),
     *      ),
     *    ),
     * )
     * 
     * Get All DataUseRegisters
     *
     * @return JsonResponse
     */

     public function index(): JsonResponse
     {
         $data_use_registers = DataUseRegister::with(['team', 'user'])->paginate(Config::get('constants.per_page'));
 
         return response()->json(
             $data_use_registers
         );
     }

     /**
     * @OA\Get(
     *    path="/api/v1/datauseregisters/{id}",
     *    operationId="fetch_data_use_registers",
     *    tags={"DataUseRegisters"},
     *    summary="DataUseRegisterController@show",
     *    description="Get data use register by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="data use register id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="data use register id",
     *       ),
     *    ),
     *   @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                property="data", 
     *                type="array",
     *                @OA\Items(type="object", 
     *                  @OA\Property(property="id", type="integer", example="1"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="counter", type="integer", example="1"),
     *                  @OA\Property(property="keywords", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="dataset_ids", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="gateway_dataset_ids", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="non_gateway_dataset_ids", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="gateway_applicants", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="non_gateway_applicants", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="funders_and_sponsors", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="other_approval_committees", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="gateway_output_tools", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="gateway_output_papers", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="non_gateway_outputs", type="string", example="minus deserunt dolorum"),
     *                  @OA\Property(property="project_title", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="project_id_text", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="organisation_name", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="organisation_sector", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="lay_summary", type="string", example="Sit quisquam est recusandae."),
     *                  @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="enabled", type="boolean", example="false"),
     *                  @OA\Property(property="team", type="object", 
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                      @OA\Property(property="name", type="string", example="someName"),
     *                      @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                      @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                      @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                      @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                      @OA\Property(property="is_admin", type="boolean", example="1"),
     *                      @OA\Property(property="member_of", type="string", example="someOrg"),
     *                      @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                      @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                      @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"), 
     *                  ),
     *                  @OA\Property(property="user", type="object", 
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="Rocio Mayer"),
     *                     @OA\Property(property="firstname", type="string", example="something or null"),
     *                     @OA\Property(property="lastname", type="string", example="something or null"),
     *                     @OA\Property(property="email", type="string", example="stanton.sibyl@example.net"),
     *                     @OA\Property(property="email_verified_at", type="integer", example="2023-05-18T01:25:00.000000Z"),
     *                     @OA\Property(property="providerid", type="string", example="something or null"),
     *                     @OA\Property(property="provider", type="string", example="something or null"),
     *                     @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                     @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                     @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  ),
     *                  @OA\Property(property="last_activity", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="manual_upload", type="boolean", example="false"),
     *                  @OA\Property(property="rejection_reason", type="string", example="Sit quisquam est recusandae."),
     *               ),
     *            ),
     *         ),
     *      ),
     *   ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       ),
     *    ),
     * )
     * 
     * Get Data Use Registers by id
     *
     * @param Request $request
     * @param integer $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $reviews = DataUseRegister::with(['team', 'user'])
                        ->where(['id' => $id])
                        ->get();

            if ($reviews->count()) {
                return response()->json([
                    'message' => 'success',
                    'data' => $reviews,
                ], 200);
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/datauseregisters",
     *    operationId="create_data_use_registers",
     *    tags={"DataUseRegisters"},
     *    summary="DataUseRegisterController@store",
     *    description="Create a new data use register",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="counter", type="integer", example="1"),
     *             @OA\Property(property="keywords", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="dataset_ids", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="gateway_dataset_ids", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="non_gateway_dataset_ids", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="gateway_applicants", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="non_gateway_applicants", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="funders_and_sponsors", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="other_approval_committees", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="gateway_output_tools", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="gateway_output_papers", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="non_gateway_outputs", type="string", example="minus deserunt dolorum"),
     *             @OA\Property(property="project_title", type="string", example="Sit quisquam est recusandae."),
     *             @OA\Property(property="project_id_text", type="string", example="Sit quisquam est recusandae."),
     *             @OA\Property(property="organisation_name", type="string", example="Sit quisquam est recusandae."),
     *             @OA\Property(property="organisation_sector", type="string", example="Sit quisquam est recusandae."),
     *             @OA\Property(property="lay_summary", type="string", example="Sit quisquam est recusandae."),
     *             @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-11 12:00:00"),
     *             @OA\Property(property="enabled", type="boolean", example="false"),
     *             @OA\Property(property="team_id", type="integer", example="1"), 
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *             @OA\Property(property="last_activity", type="datetime", example="2023-04-11 12:00:00"),
     *             @OA\Property(property="manual_upload", type="boolean", example="false"),
     *             @OA\Property(property="rejection_reason", type="string", example="Sit quisquam est recusandae."),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=201,
     *       description="Created",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="data", type="integer", example="100")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error"),
     *       )
     *    )
     * )
     * 
     * Create a new data use register
     *
     * @param CreateDataUseRegisterRequest $request
     * @return JsonResponse
     */
    public function store(CreateDataUseRegisterRequest $request): JsonResponse
    {
        try {
            $input = $request->all();

            $datauseregister = DataUseRegister::create([
                'counter' => (int) $input['counter'],
                # loads of jsons
                // 'project_title' => $input['project_title'],
                // 'project_id_text' => $input['project_id_text'],
                // 'organisation_name' => $input['organisation_name'],
                // 'organisation_sector' => $input['organisation_sector'],
                // 'lay_summary' => $input['lay_summary'],
                'team_id' => (int) $input['team_id'],
                'user_id' => (int) $input['user_id'],
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $datauseregister->id,
            ], 201);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // TODO
    /**
     * @OA\Put(
     *    path="/api/v1/users",
     *    operationId="update_users",
     *    tags={"Users"},
     *    summary="UserController@update",
     *    description="Update user",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="enabled",
     *                type="boolean",
     *                example=true,
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="success",
     *          ),
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=400,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="bad request"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       )
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     * 
     * Update data use register
     *
     * @param UpdateDataUseRegisterRequest $request
     * @param integer $id
     * @return mixed
     */
    public function update(UpdateDataUseRegisterRequest $request, int $id): mixed
    {
        try {
            $input = $request->all();

            if (!$input) {
                return response()->json([
                    'message' => 'bad request',
                ], 400);
            }

            $datauseregister = DataUseRegister::findOrFail($id);
            if ($datauseregister) {
                $array = [
                    "counter" => (int) $input['counter'],
                    "team_id" => (int) $input['team_id'],
                    "user_id" => (int) $input['user_id'],                   
                ];

                $datauseregister->update($array);

                return response()->json([
                    'message' => 'success',
                    'data' => $datauseregister
                ], 202);
            }

            return response()->json([
                'message' => 'not found',
            ], 404);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // TODO
    /**
     * @OA\Delete(
     *    path="api/v1/users/{id}",
     *    operationId="delete_users",
     *    tags={"Users"},
     *    summary="UserController@destroy",
     *    description="Delete User based in id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="user id",
     *       )
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       )
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       )
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $datauseregisters = DataUseRegister::where('id', $id)->get();

            if ($datauseregisters) {
                DataUseRegister::where('id', $id)->delete();

                return response()->json([
                    'message' => 'success',
                ], 200);
            }

            return response()->json([
                'message' => 'not found',
            ], 404);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
