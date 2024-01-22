<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrganisationController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/organisations",
     *      summary="List of organisations",
     *      description="Returns a list of organisations enabled on the system",
     *      tags={"Organisation"},
     *      summary="Organisation@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="perPage",
     *          in="query",
     *          description="Specify number of results per page",
     *          @OA\Schema(type="integer")
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
     *                      @OA\Property(property="name", type="string", example="Name"),
     *                      @OA\Property(property="enabled", type="boolean", example="1")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = request('perPage', Config::get('constants.per_page'));
        $organisations = Organisation::where('enabled', 1)->paginate($perPage);
        return response()->json(
            $organisations,
        );
    }

    /**
     * @OA\Get(
     *      path="/api/v1/organisations/{id}",
     *      summary="Return a single organisation",
     *      description="Return a single organisation",
     *      tags={"Organisation"},
     *      summary="Organisation@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="organisation id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="organisation id",
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
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="enabled", type="boolean", example="1")
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
            $organisation = Organisation::where(['id' => $id,])->with(['users'])->get();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $organisation,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/organisations",
     *      summary="Create a new organisation",
     *      description="Creates a new organisation",
     *      tags={"Organisation"},
     *      summary="Organisation@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Organisation definition",
     *          @OA\JsonContent(
     *              required={"name", "enabled"},
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="enabled", type="boolean", example="true"),
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
        try {
            $input = $request->all();

            $organisation = Organisation::create([
                'name' => $input['name'],
                'enabled' => $input['enabled'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $organisation->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/organisations/{id}",
     *      summary="Update a organisation",
     *      description="Update a organisation",
     *      tags={"Organisation"},
     *      summary="Organisation@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="organisation id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="organisation id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Organisation definition",
     *          @OA\JsonContent(
     *              required={"name", "enabled"},
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="enabled", type="string", example="true"),
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
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
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
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            $organisation = Organisation::where('id', $id)->update([
                'name' => $input['name'],
                'enabled' => $input['enabled'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Organisation::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/organisations/{id}",
     *      summary="Edit a organisation",
     *      description="Edit a organisation",
     *      tags={"Organisation"},
     *      summary="Organisation@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="organisation id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="organisation id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Organisation definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Name"),
     *              @OA\Property(property="enabled", type="string", example="true"),
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
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="Name"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
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
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $arrayKeys = [
                'name',
                'enabled',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Organisation::where('id', $id)->update($array);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Organisation::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/organisations/{id}",
     *      summary="Delete a organisation",
     *      description="Delete a organisation",
     *      tags={"Organisation"},
     *      summary="Organisation@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="organisation id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="organisation id",
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
    public function destroy(Request $request, int $id): JsonResponse
    {
        $organisation = Organisation::findOrFail($id);
        if ($organisation) {
            $organisation->enabled = false;
            if ($organisation->save()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
            ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }
}
