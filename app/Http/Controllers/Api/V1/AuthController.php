<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\JwtController;

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
     *             example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiIiLCJzdWIiOiJkYW4gbml0YSIsImF1ZCI6IiIsImlhdCI6IjE2ODAxNzg2NjEiLCJuYmYiOiIxNjgwMTc4NjYxIiwiZXhwIjoiMTY4MDE3ODY2MSIsImp0aSI6IiIsInVzZXIiOnsiaWQiOiIxIiwibmFtZSI6ImRhbiBuaXRhIiwiZW1haWwiOiJuaXRhLmRhbkBnbWFpbC5jb20ifX0.6DdcPUUhv4t2zVO4nfvRg5vp_EGeiJsr5ZBseAlL9Vw"
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
     *
     * @param Request $request
     */
    public function checkAuthorization(Request $request)
    {
        $input = $request->all();

        $user = User::where('email', $input['email'])
            ->where('provider', Config::get('constants.provider.service'))
            ->first();
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
            "access_token" => $jwt,
            "token_type" => "bearer"
        ])->setStatusCode(200);
    }

    /**
     * create JWT token
     */
    private function createJwt($user)
    {
        $currentTime = CarbonImmutable::now();
        $expireTime = $currentTime->addSeconds(env('JWT_EXPIRATION'));

        $userClaims = [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        $arrayClaims = [
            'iss' => (string) env('APP_URL'),
            'sub' => (string) $user->name,
            'aud' => (string) env('APP_NAME'),
            'iat' => (string) strtotime($currentTime),
            'nbf' => (string) strtotime($currentTime),
            'exp' => (string) strtotime($expireTime),
            'jti' => (string) env('JWT_SECRET'),
            'user' => $userClaims,
        ];

        $this->jwt->setPayload($arrayClaims);
        return $this->jwt->create();
    }
}
