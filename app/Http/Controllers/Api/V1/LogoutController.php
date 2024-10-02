<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LogoutController extends Controller
{
    /**
     * @OA\Post(
     *    path="/api/v1/logout",
     *    operationId="logout",
     *    tags={"Logout"},
     *    summary="LogoutController@logout",
     *    description="logout",
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="success",
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     * )
     *
     * @param Request $request
     * @return mixed
     */
    public function logout(Request $request): mixed
    {
        $request->session()->flush();
        return response()->json([
            'message' => 'OK',
        ], 200);
    }
}
