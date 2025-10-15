<?php

namespace App\Http\Controllers;

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
     *    summary="WidgetController@index",
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
                'widget_name',
                'size_width',
                'size_height',
                'updated_at',
                'unit'
            ]);

        return response()->json([ 'data' => $widgets]);
    }
}
