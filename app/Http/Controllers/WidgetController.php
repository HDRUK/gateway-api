<?php

namespace App\Http\Controllers;

use Config;
use App\Models\Widget;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WidgetController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/v1/widgets",
     *    deprecated=true,
     *    operationId="fetch_all_widgets",
     *    tags={"Widgets"},
     *    summary="WidgetController@get",
     *    description="Get All Widgets",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          )
     *       )
     *    )
     * )
     */
    public function get(Request $request): JsonResponse
    {
        $teamId = $request->query('team_id', null);
        \Log::info('This is a log message.'. $teamId);

        $widgets = Widget::where('team_id', $teamId)
            ->get([
                'id',
                'widget_name',
                'size_width',
                'size_height',
                'updated_at',
                'unit'
            ]);

        return response()->json([ 'data' => $widgets]);
    }



    /**
    * @OA\Delete(
    *      path="/api/v1/widgets/{id}",
    *      deprecated=true,
    *      operationId="delete_widget",
    *      summary="Delete a widget",
    *      description="Delete a widget",
    *      tags={"Widgets"},
    *      summary="WidgetController@destroy",
    *      security={{"bearerAuth":{}}},
    *      @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="widget id",
    *         required=true,
    *         example="1",
    *         @OA\Schema(
    *            type="integer",
    *            description="widget id",
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
    public function destroy(Request $request, string $id) // softdelete
    {
        try {
            $input = $request->all();
            Widget::where('id', $id)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {


            throw new Exception($e->getMessage());
        }
    }
}
