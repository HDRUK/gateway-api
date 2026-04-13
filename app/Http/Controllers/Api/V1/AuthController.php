<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\JwtController;
use App\Exceptions\UnauthorizedException;

class AuthController extends Controller
{
    private JwtController $jwt;

    /**
     * constructor
     */
    public function __construct(JwtController $jwt)
    {
        $this->jwt = $jwt;
    }

   /**
     * @OA\Post(
     *    path="/api/v1/auth/cookie",
     *    operationId="authenticationWithCookie",
     *    tags={"Authentication"},
     *    summary="AuthController@checkAuthorizationWithCookie",
     *    description="Generate Jwt and return it as a cookie based on email and password with dynamic lifetime",
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials and optional cookie lifetime in minutes",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="email",
     *                type="string",
     *                example="user1@mail.com",
     *                description="Email"
     *             ),
     *             @OA\Property(
     *                property="password",
     *                type="string",
     *                example="password123!",
     *                description="Password"
     *             ),
     *             @OA\Property(
     *                property="cookie_lifetime",
     *                type="integer",
     *                example=1440,
     *                description="Optional: cookie lifetime in minutes (default 1440 = 1 day)"
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response with JWT cookie",
     *    ),
     *    @OA\Response(
     *       response="401",
     *       description="Unauthorized",
     *    ),
     * )
     */
    public function checkAuthorizationWithCookie(Request $request)
    {
        try {
            $input = $request->all();

            $user = User::where('email', $input['email'])
                        ->where('provider', Config::get('constants.provider.service')) //this could be whoever
                        ->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 401);
            }

            if (!Hash::check($input['password'], $user['password'])) {
                return response()->json(['message' => 'Password is not matching'], 401);
            }

            $jwt = $this->createJwt($user);

            Auditor::log([
                'user_id' => $user['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Authorization for user via cookie",
            ]);

            // Dynamic cookie lifetime with max of 7 days (10080 minutes)
            $requestedLifetime = isset($input['cookie_lifetime']) ? (int)$input['cookie_lifetime'] : 1440;
            $cookieLifetime = min($requestedLifetime, 10080); // cap at 7 days

            return response()->json(['message' => 'Login successful'])
                            ->cookie('token', $jwt, $cookieLifetime, null, null, false, true);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/auth/signout",
     *    operationId="signOut",
     *    tags={"Authentication"},
     *    summary="AuthController@signOut",
     *    description="Logs out the user by deleting the JWT cookie",
     *    @OA\Response(
     *       response="200",
     *       description="Successfully logged out",
     *    ),
     * )
     */
    public function signOut(Request $request)
    {
        try {
            Auditor::log([
                'user_id' => auth()->id() ?? null,
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "User logged out and cookie cleared",
            ]);

            // Expire the JWT cookie
            return response()->json(['message' => 'Successfully logged out'])
                            ->cookie('token', null, 0, null, null, false, true);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }



    /**
     * @OA\Post(
     *    path="/api/v1/auth",
     *    operationId="authentication",
     *    tags={"Authentication"},
     *    summary="AuthController@checkAuthorization",
     *    description="Generate Jwt based on email and password",
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="email",
     *                type="string",
     *                example="user1@mail.com",
     *                description="Email"
     *             ),
     *             @OA\Property(
     *                property="password",
     *                type="string",
     *                example="password123!",
     *                description="Password"
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="access_token",
     *             type="string",
     *             example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9_jwt_token"
     *          ),
     *          @OA\Property(
     *             property="token_type",
     *             type="string",
     *             example="bearer",
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="401",
     *       description="Missing Property",
     *    ),
     * )
     */
    public function checkAuthorization(Request $request)
    {
        try {
            $input = $request->all();

            $user = User::where('email', $input['email'])->where('provider', Config::get('constants.provider.service'))->first();
            if (!$user) {
                throw new Exception("User not found");
            }

            if (!Hash::check($input['password'], $user['password'])) {
                throw new Exception("Password is not matching");
            }

            $jwt = $this->createJwt($user);

            Auditor::log([
                'user_id' => $user['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Autorization for user",
            ]);

            return response()->json([
                'access_token' => $jwt,
                'token_type' => 'bearer'
            ])->setStatusCode(200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/refresh_token",
     *    operationId="refresh_token",
     *    tags={"Authentication"},
     *    summary="AuthController@refreshToken",
     *    description="Regenerate jwt token",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="access_token",
     *             type="string",
     *             example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9_jwt_token"
     *          ),
     *          @OA\Property(
     *             property="token_type",
     *             type="string",
     *             example="bearer",
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="401",
     *       description="Missing Property",
     *    ),
     * )
     */
    public function refreshToken(Request $request)
    {
        try {
            $input = $request->all();

            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            if (!count($jwtUser)) {
                throw new UnauthorizedException();
            }

            $userId = $jwtUser['id'];
            $user = User::where(['id', $userId])->first();
            if (is_null($user)) {
                throw new UnauthorizedException();
            }

            $jwt = $this->createJwt($user);

            Auditor::log([
                'user_id' => $user['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Refresh Token",
            ]);

            return response()->json([
                "access_token" => $jwt,
                "token_type" => "bearer"
            ])->setStatusCode(200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * create JWT token
     *
     * @param User $user
     * @return string
     */
    private function createJwt(User $user): string
    {
        return $this->jwt->generateToken($user->id);
    }
}
