<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Illuminate\Http\Request;
use App\Models\DataUseRegister;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Requests\DataUseRegister\EditDataUseRegister;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\DataUseRegister\GetDataUseRegister;
use App\Http\Requests\DataUseRegister\CreateDataUseRegister;
use App\Http\Requests\DataUseRegister\DeleteDataUseRegister;
use App\Http\Requests\DataUseRegister\UpdateDataUseRegister;

class ROCrateParser {
    
    public static function extract_dur_details(array $ro_crate) {

        // Convert $ro_crate @graph entry to associative array with keys from @id fields.
        $myArray = array();
        foreach ($ro_crate["@graph"] as $object) {
            $myArray[$object["@id"]] = $object;
        }

        // Find the id of the source organization
        $sourceOrganization = null;
        foreach ($myArray as $object) {
            if (isset($object["@type"]) && $object["@type"] == "Dataset" && isset($object["sourceOrganization"])) {
                $sourceOrganization = $object["sourceOrganization"]["@id"];
            }
        }

        // Find project_title, lay_summary and public_benefit_statement from the Project.
        $project_title = null;
        $lay_summary = null;
        $public_benefit_statement = null;
        if (isset($myArray[$sourceOrganization]) && $myArray[$sourceOrganization]["@type"] == 'Project') {
            $project_title = $myArray[$sourceOrganization]["name"];
            // lay_summary = Dataset.sourceOrganization -> Project.description
            // (not guaranteed by 5 Safes RO-Crate spec at this time)
            if (isset($myArray[$sourceOrganization]["description"])) {
                $lay_summary = $myArray[$sourceOrganization]["description"];
            }
            else {
                $lay_summary = "Not provided";
            }

            // public_benefit_statement = Dataset.sourceOrganization -> Project.publishingPrinciples -> CreativeWork.text 
            // (not guaranteed by 5 Safes RO-Crate spec at this time)
            if (isset($myArray[$sourceOrganization]["publishingPrinciples"]) && 
            isset($myArray[$myArray[$sourceOrganization]["publishingPrinciples"]["@id"]]) &&
            isset($myArray[$myArray[$myArray[$sourceOrganization]["publishingPrinciples"]["@id"]]]["text"])) {
                $public_benefit_statement = $myArray[$myArray[$myArray[$sourceOrganization]["publishingPrinciples"]["@id"]]]["text"];
            }
            else
            {
                $public_benefit_statement = "Not provided";
            }

        }

        // Find organization_name = Dataset.mentions -> CreateAction.agent -> Person.affiliation -> Organization.name

        // Firstly, find Dataset.
        $mentions = null;
        foreach ($myArray as $object) {
            if (isset($object["@type"]) && $object["@type"] == "Dataset" && isset($object["mentions"])) {
                $mentions = $object["mentions"]["@id"];
            }
        }

        $organization_name = null;
        if (isset($myArray[$mentions]) && $myArray[$mentions]["@type"] == 'CreateAction') {
            $createAction_agent_id = $myArray[$mentions]["agent"]["@id"];
            $agent = $myArray[$createAction_agent_id];
            
            $affiliation_id = $myArray[$createAction_agent_id]["affiliation"]["@id"];
            $affiliation = $myArray[$affiliation_id];
            
            $organization_name = $affiliation["name"];
        }

        $return_array = [
            "organization_name" => $organization_name,
            "project_title" => $project_title,
            "lay_summary" => $lay_summary,
            "public_benefit_statement" => $public_benefit_statement,
        ];

        return $return_array;
    }
}

class DataUseRegisterController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *    path="/api/v1/data_use_registers",
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
     *                  @OA\Property(property="dataset_id", type="integer", example="1"),
     *                  @OA\Property(property="enabled", type="boolean", example="false"),
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
     *                  @OA\Property(property="ro_crate", type="string", example="Sit quisquam est recusandae."),
     *               ),
     *            ),
     *         ),
     *      ),
     *    ),
     * )
     */

     public function index(Request $request): JsonResponse
     {
         $data_use_registers = DataUseRegister::with('user')->paginate(Config::get('constants.per_page'), ['*'], 'page');
 
         return response()->json(
             $data_use_registers
         );
     }

     /**
     * @OA\Get(
     *    path="/api/v1/data_use_registers/{id}",
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
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data", 
     *             type="array",
     *             @OA\Items(type="object", 
     *                @OA\Property(property="id", type="integer", example="1"),
     *                @OA\Property(property="dataset_id", type="integer", example="1"),
     *                @OA\Property(property="enabled", type="boolean", example="false"),
     *                @OA\Property(property="user", type="object", 
     *                  @OA\Property(property="id", type="integer", example="1"),
     *                  @OA\Property(property="name", type="string", example="Rocio Mayer"),
     *                  @OA\Property(property="firstname", type="string", example="something or null"),
     *                  @OA\Property(property="lastname", type="string", example="something or null"),
     *                  @OA\Property(property="email", type="string", example="stanton.sibyl@example.net"),
     *                  @OA\Property(property="email_verified_at", type="integer", example="2023-05-18T01:25:00.000000Z"),
     *                  @OA\Property(property="providerid", type="string", example="something or null"),
     *                  @OA\Property(property="provider", type="string", example="something or null"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *               ),
     *               @OA\Property(property="ro_crate", type="string", example="Sit quisquam est recusandae."),
     *            ),
     *         ),
     *      ),
     *   ),
     *   @OA\Response(
     *      response=401,
     *      description="Unauthorized",
     *      @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="unauthorized")
     *      )
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="Not found response",
     *      @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="not found"),
     *      )
     *   )
     * )
     */
    public function show(GetDataUseRegister $request, int $id): JsonResponse
    {
        try {
            $data_use_registers = DataUseRegister::with('user')
                        ->where(['id' => $id])
                        ->get();

            if ($data_use_registers->count()) {
                return response()->json([
                    'message' => 'success',
                    'data' => $data_use_registers,
                ], 200);
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/data_use_registers",
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
     *             @OA\Property(property="id", type="integer", example="1"),
     *             @OA\Property(property="dataset_id", type="integer", example="1"),
     *             @OA\Property(property="enabled", type="boolean", example="false"),
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *             @OA\Property(property="ro_crate", type="string", example="Sit quisquam est recusandae."),
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
     */
    public function store(CreateDataUseRegister $request): JsonResponse
    {
        try {
            $input = $request->all();

            $dur_details = ROCrateParser::extract_dur_details($input['ro_crate']);
            
            $data_use_registers = DataUseRegister::create([
                'dataset_id' => (int) $input['dataset_id'],
                'enabled' => $input['enabled'] ?? null,
                'user_id' => (int) $input['user_id'] ?? null,
                'ro_crate' => json_encode($input['ro_crate']) ?? null,
                'organization_name' => $dur_details['organization_name'],
                'project_title' => $dur_details['project_title'],
                'lay_summary' => $dur_details['lay_summary'],
                'public_benefit_statement' => $dur_details['public_benefit_statement'],
            ]);

            return response()->json([
                'message' => 'info',
                'data' => $data_use_registers->id,
            ], 201);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/data_use_registers/{id}",
     *    operationId="update_data_use_registers",
     *    tags={"DataUseRegisters"},
     *    summary="DataUseRegisterController@update",
     *    description="Update data use register",
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
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="id", type="integer", example="1"),
     *             @OA\Property(property="dataset_id", type="integer", example="1"),
     *             @OA\Property(property="enabled", type="boolean", example="false"),
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *             @OA\Property(property="ro_crate", type="string", example="Sit quisquam est recusandae."),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
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
     */
    public function update(UpdateDataUseRegister $request, int $id): mixed
    {
        try {
            $input = $request->all();

            DataUseRegister::where('id', $id)->update([
                'dataset_id' => (int) $input['dataset_id'],
                'enabled' => $input['enabled'] ?? null,
                'user_id' => (int) $input['user_id'] ?? null,
                'ro_crate' => $input['ro_crate'] ?? null,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => DataUseRegister::where('id', $id)->first()
            ], 202);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/data_use_registers/{id}",
     *    operationId="edit_data_use_registers",
     *    tags={"DataUseRegisters"},
     *    summary="DataUseRegisterController@edit",
     *    description="Edit data use register",
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
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="id", type="integer", example="1"),
     *             @OA\Property(property="dataset_id", type="integer", example="1"),
     *             @OA\Property(property="enabled", type="boolean", example="false"),
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *             @OA\Property(property="ro_crate", type="string", example="Sit quisquam est recusandae."),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
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
     */
    public function edit(EditDataUseRegister $request, int $id): mixed
    {
        try {
            $input = $request->all();
            $arrayKeys = [
                'dataset_id',
                'enabled',
                'user_id',
                'ro_crate',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            DataUseRegister::where('id', $id)->update($array);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataUseRegister::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="api/v1/data_use_registers/{id}",
     *    operationId="delete_data_use_register",
     *    tags={"DataUseRegisters"},
     *    summary="DataUseRegisterController@destroy",
     *    description="Delete Data Use Register based in id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="data use register id",
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
    public function destroy(DeleteDataUseRegister $request, int $id): JsonResponse
    {
        try {
            DataUseRegister::where('id', $id)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
