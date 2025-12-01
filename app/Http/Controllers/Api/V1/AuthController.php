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
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;

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
     * @OA\Post(
     *    path="/api/v1/auth/register",
     *    operationId="register",
     *    tags={"Authentication"},
     *    summary="AuthController@register",
     *    description="Register a new user with email and password",
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user registration data",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="email",
     *                type="string",
     *                example="john@example.com",
     *                description="Email address"
     *             ),
     *             @OA\Property(
     *                property="password",
     *                type="string",
     *                example="SecurePassword123!",
     *                description="Password (minimum 8 characters)"
     *             ),
     *             @OA\Property(
     *                property="firstname",
     *                type="string",
     *                example="John",
     *                description="First name (optional)"
     *             ),
     *             @OA\Property(
     *                property="lastname",
     *                type="string",
     *                example="Doe",
     *                description="Last name (optional)"
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
     *          @OA\Property(
     *             property="user",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="400",
     *       description="Validation error",
     *    ),
     *    @OA\Response(
     *       response="409",
     *       description="Email already exists",
     *    ),
     * )
     */
    public function register(RegisterRequest $request)
    {
        try {
            $input = $request->validated();

            // Build name from firstname and lastname if provided
            $name = trim(($input['firstname'] ?? '') . ' ' . ($input['lastname'] ?? ''));
            if (empty($name)) {
                $name = $input['email']; // Fallback to email if no name provided
            }

            // Create user
            $user = User::create([
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'name' => $name,
                'firstname' => $input['firstname'] ?? null,
                'lastname' => $input['lastname'] ?? null,
                'provider' => Config::get('constants.provider.service'),
            ]);

            // Generate JWT token
            $jwt = $this->createJwt($user);

            // Log the registration
            Auditor::log([
                'user_id' => $user->id,
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => "User registered: {$user->email}",
            ]);

            return response()->json([
                'access_token' => $jwt,
                'token_type' => 'bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ])->setStatusCode(200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/auth/login",
     *    operationId="login",
     *    tags={"Authentication"},
     *    summary="AuthController@login",
     *    description="Login with email and password",
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="email",
     *                type="string",
     *                example="john@example.com",
     *                description="Email address"
     *             ),
     *             @OA\Property(
     *                property="password",
     *                type="string",
     *                example="SecurePassword123!",
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
     *          @OA\Property(
     *             property="user",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="400",
     *       description="Validation error",
     *    ),
     *    @OA\Response(
     *       response="401",
     *       description="Invalid credentials",
     *    ),
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $input = $request->validated();

            // Find user by email and provider
            $user = User::where('email', $input['email'])
                ->where('provider', Config::get('constants.provider.service'))
                ->first();

            if (!$user) {
                throw new UnauthorizedException('Invalid credentials');
            }

            // Verify password
            if (!Hash::check($input['password'], $user->password)) {
                throw new UnauthorizedException('Invalid credentials');
            }

            // Generate JWT token
            $jwt = $this->createJwt($user);

            // Log the login
            Auditor::log([
                'user_id' => $user->id,
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => "User logged in: {$user->email}",
            ]);

            return response()->json([
                'access_token' => $jwt,
                'token_type' => 'bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ])->setStatusCode(200);
        } catch (UnauthorizedException $e) {
            throw $e;
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
