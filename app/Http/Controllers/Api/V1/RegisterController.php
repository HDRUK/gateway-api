<?php

namespace App\Http\Controllers\Api\V1;

use Hash;
use Config;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Controllers\JwtController;

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
     *                property="name",
     *                type="string",
     *                example="John Doe",
     *                example="Name",
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
     * @param RegisterRequest $request
     */
    public function create(RegisterRequest $request)
    {
        $input = $request->all();

        $user = User::where([
            'provider' => 'internal',
            'email' => $input['email'],
        ])->first();

        if (!$user) {
            $user = $this->saveUser($input);
        }

        $jwt = $this->createJwt($user);

        return response()->json([
            "access_token" => $jwt,
            "token_type" => "bearer"
        ])->setStatusCode(200);
    }

    /**
     * save user in database
     */
    private function saveUser($data)
    {
        try {
            $user = new User();
            $user->name = $data['name'];
            $user->firstname = null;
            $user->lastname = null;
            $user->email = $data['email'];
            $user->provider = Config::get('constants.provider.service');
            $user->password = Hash::make($data['password']);
            $user->save();

            return $user;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * create JWT token
     */
    private function createJwt($user)
    {
        $currentTime = Carbon::now();

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
            'exp' => (string) strtotime($currentTime->addSeconds(env('JWT_EXPIRATION'))),
            'jti' => (string) env('JWT_SECRET'),
            'user' => $userClaims,
        ];

        $this->jwt->setPayload($arrayClaims);
        $jwt = $this->jwt->create();
        return $jwt;
    }
}
