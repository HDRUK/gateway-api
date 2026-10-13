<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Auth\TokenDenylist;
use App\Http\Controllers\JwtController;
use App\Http\Controllers\Controller;

class LogoutController extends Controller
{
    /**
     * @OA\Post(
     *      x={"internal"="true"},
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
    public function logout(Request $request, TokenDenylist $denylist): mixed
    {
        // Withdraw the token the caller presented. Flushing the session
        // alone used to be the whole of logout, but the JWT flow never
        // touches a session, so the bearer token carried on working for
        // the rest of its lifetime.
        $this->revokePresentedToken($request, $denylist);

        $request->session()->flush();

        return response()->json([
            'message' => 'OK',
        ], 200);
    }

    /**
     * JwtMiddleware merges the raw token as `jwt` for both the cookie and
     * bearer paths, so logout can withdraw whichever one was used.
     */
    private function revokePresentedToken(Request $request, TokenDenylist $denylist): void
    {
        $token = (string) $request->input('jwt');

        if ($token === '') {
            return;
        }

        $jwt = new JwtController();
        $jwt->setJwt($token);
        $claims = $jwt->decode();

        if (isset($claims['jti'], $claims['exp'])) {
            $denylist->revoke((string) $claims['jti'], $claims['exp']);
        }
    }
}
