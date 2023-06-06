<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use App\Models\Sector;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SectorController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/sectors",
     *      summary="List of system sectors",
     *      description="Returns a list of sectors enabled on the system",
     *      tags={"Sector"},
     *      summary="Sector@index",
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
     *                      @OA\Property(property="name", type="string", example="Name"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $sectors = Sector::where('enabled', 1)->paginate(Config::get('constants.per_page'));
        return response()->json([
            'data' => $sectors,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v1/sectors/{id}",
     *      summary="Return a single system sector",
     *      description="Return a single system sector",
     *      tags={"Sector"},
     *      summary="Sector@show",
     *      security={{"bearerAuth":{}}},
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
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
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
    public function show(Request $request, int $id)
    {
        $sector = Sector::findOrFail($id);
        if ($sector) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $sector,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    /**
     * @OA\Post(
     *      path="/api/v1/sectors",
     *      summary="Create a new system sector",
     *      description="Creates a new system sector",
     *      tags={"Sector"},
     *      summary="Sector@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Sector definition",
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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'enabled' => 'required',
        ]);

        $sector = Sector::create($request->post());
        if ($sector) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $sector->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
        ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
    }

    /**
     * @OA\Put(
     *      path="/api/v1/sectors/{id}",
     *      summary="Update a system sector",
     *      description="Update a system sector",
     *      tags={"Sector"},
     *      summary="Sector@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Sector definition",
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
     *                  @OA\Property(property="enabled", type="boolean", example="true"),
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
    public function update(Request $request, int $sector)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'enabled' => 'required',
        ]);

        $sector = Sector::findOrFail($sector);
        $body = $request->post();
        $sector->name = $body['name'];
        $sector->enabled = $body['enabled'];

        if ($sector->save()) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $sector,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message')
        ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/sectors/{id}",
     *      summary="Delete a system sector",
     *      description="Delete a system sector",
     *      tags={"Sector"},
     *      summary="Sector@destroy",
     *      security={{"bearerAuth":{}}},
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
    public function destroy(Request $request, int $sector)
    {
        $sector = Sector::findOrFail($sector);
        if ($sector) {
            $sector->enabled = false;
            if ($sector->save()) {
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
