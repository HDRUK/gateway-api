<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function checkAuthorization(Request $request, Response $response) 
    {
        $input = $request->all();

        $user = User::where('email', $input['email'])->where('provider', Config::get('constants.provider.service'))->first();
        if (!$user) {
            throw new Exception("User not found");
        }

        if (!Hash::check($input['password'], $user['password'])) {
            throw new Exception("Password is not matching");
        }

        $jwt = $this->createJwt($user);

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
            'exp' => (string) strtotime($currentTime->addDays(7)),
            'jti' => (string) env('JWT_SECRET'),
            'user' => $userClaims,
        ];

        $this->jwt->setPayload($arrayClaims);
        $jwt = $this->jwt->create();
        return $jwt;
    }
}
