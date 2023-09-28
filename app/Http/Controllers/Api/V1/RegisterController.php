<?php

namespace App\Http\Controllers\Api\V1;

use Hash;
use Config;
use Exception;
use Carbon\CarbonImmutable;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Controllers\JwtController;
use App\Http\Requests\UserRequest;
use App\Models\AuthorisationCode;

class RegisterController extends Controller
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
     *    path="/api/v1/register",
     *    operationId="register",
     *    tags={"Authentication"},
     *    summary="RegisterController@create",
     *    description="Register New User with username and password",
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="firstname",
     *                type="string",
     *                example="John",
     *             ),
     *             @OA\Property(
     *                property="lastname",
     *                type="string",
     *                example="Doe",
     *             ),
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
     * @param UserRequest $request
     */
    public function create(UserRequest $request)
    {
        try {
            $input = $request->all();

            $array = [
                "name" => $input['firstname'] . " " . $input['lastname'],
                "firstname" => $input['firstname'],
                "lastname" => $input['lastname'],
                "email" => $input['email'],
                "provider" =>  Config::get('constants.provider.service'),
                "password" => Hash::make($input['password']),
            ];
            $user = User::create($array);

            $jwt = $this->createJwt($user);

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
        $jwt = $this->jwt->create();

        AuthorisationCode::createRow([
            'user_id' => (int) $user->id,
            'jwt' => (string) $jwt,
            'created_at' => $currentTime,
            'expired_at' => $expireTime,
        ]);

        return $jwt;
    }
}
