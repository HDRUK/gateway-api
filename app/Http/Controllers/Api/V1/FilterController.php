<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Carbon\Carbon;

use App\Models\Filter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FilterController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/filters",
     *      summary="List of system filters",
     *      description="Returns a list of filters enabled on the system",
     *      tags={"Filter"},
     *      summary="Filter@index",
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
     *                      @OA\Property(property="type", type="string", example="someType"),
     *                      @OA\Property(property="value", type="string", example="some value"),
     *                      @OA\Property(property="keys", type="string", example="someKey"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $filters = Filter::where('enabled', 1)->get();
        return response()->json([
            'data' => $filters
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v1/filters/{id}",
     *      summary="Return a single system filter",
     *      description="Return a single system filter",
     *      tags={"Filter"},
     *      summary="Filter@show",
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
     *                  @OA\Property(property="type", type="string", example="someType"),
     *                  @OA\Property(property="value", type="string", example="some value"),
     *                  @OA\Property(property="keys", type="string", example="someKey"),
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
        $filter = Filter::findOrFail($id);
        if ($filter) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $filter,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    /**
     * @OA\Post(
     *      path="/api/v1/filters",
     *      summary="Create a new system filter",
     *      description="Creates a new system filter",
     *      tags={"Filter"},
     *      summary="Filter@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Filter definition",
     *          @OA\JsonContent(
     *              required={"type", "value", "keys", "enabled"},
     *              @OA\Property(property="type", type="string", example="dataset"),
     *              @OA\Property(property="value", type="string", example="your filter value here"),
     *              @OA\Property(property="keys", type="string", example="purpose"),
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
            'type' => 'required',
            'value' => 'required',
            'keys' => 'required',
            'enabled' => 'required',
        ]);

        $filter = Filter::create($request->post());
        if ($filter) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $filter->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
        ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
    }

    /**
     * @OA\Put(
     *      path="/api/v1/filters/{id}",
     *      summary="Update a system filter",
     *      description="Update a system filter",
     *      tags={"Filter"},
     *      summary="Filter@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Filter definition",
     *          @OA\JsonContent(
     *              required={"type", "value", "keys", "enabled"},
     *              @OA\Property(property="type", type="string", example="dataset"),
     *              @OA\Property(property="value", type="string", example="your filter value here"),
     *              @OA\Property(property="keys", type="string", example="purpose"),
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
     *                  @OA\Property(property="type", type="string", example="someType"),
     *                  @OA\Property(property="value", type="string", example="some value"),
     *                  @OA\Property(property="keys", type="string", example="someKey"),
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
    public function update(Request $request, int $filter)
    {
        $request->validate([
            'type' => 'required',
            'value' => 'required',
            'keys' => 'required',
            'enabled' => 'required',
        ]);

        $filter = Filter::findOrFail($filter);
        $body = $request->post();
        $filter->type = $body['type'];
        $filter->value = $body['value'];
        $filter->keys = $body['keys'];
        $filter->enabled = $body['enabled'];

        if ($filter->save()) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $filter,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } else {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
            ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/filters/{id}",
     *      summary="Delete a system filter",
     *      description="Delete a system filter",
     *      tags={"Filter"},
     *      summary="Filter@destroy",
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
    public function destroy(Request $request, int $filter)
    {
        $filter = Filter::findOrFail($filter);
        if ($filter) {
            $filter->deleted_at = Carbon::now();
            $filter->enabled = false;
            if ($filter->save()) {
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
