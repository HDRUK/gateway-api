<?php

namespace App\Http\Controllers;

use App\Models\Widget;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WidgetController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/widgets",
     *    deprecated=true,
     *    operationId="fetch_all_widgets",
     *    tags={"Widgets"},
     *    summary="WidgetController@get",
     *    description="Get All Widgets",
     *    security={{"bearerAuth":{}}},
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
    public function get(Request $request, int $teamId): JsonResponse
    {
        //\Log::info('This is a log message.'. $teamId);

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
 *      path="/api/v1/teams/{teamId}/widgets/{id}",
 *      operationId="delete_widget",
 *      summary="Delete a widget",
 *      description="Soft delete a widget belonging to a specific team",
 *      tags={"Widgets"},
 *      security={{"bearerAuth":{}}},
 *      @OA\Parameter(
 *         name="teamId",
 *         in="path",
 *         description="Team ID",
 *         required=true,
 *         example="5",
 *         @OA\Schema(type="integer"),
 *      ),
 *      @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Widget ID",
 *         required=true,
 *         example="1",
 *         @OA\Schema(type="integer"),
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Widget not found",
 *          @OA\JsonContent(
 *              @OA\Property(property="message", type="string", example="not found")
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Widget deleted successfully",
 *          @OA\JsonContent(
 *              @OA\Property(property="message", type="string", example="success")
 *          )
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Server error",
 *          @OA\JsonContent(
 *              @OA\Property(property="message", type="string", example="error")
 *          )
 *      )
 * )
 */
    public function destroy(Request $request, int $teamId, int $id)
    {
        try {
            $widget = Widget::where('id', $id)
                ->where('team_id', $teamId)
                ->first();

            if (! $widget) {
                return response()->json([
                    'message' => 'not found',
                ], 404);
            }

            $widget->delete();

            return response()->json([
                'message' => 'success',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
