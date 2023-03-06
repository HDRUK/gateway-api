<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
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
     * register user
     */
    public function create(RegisterRequest $request, Response $response)
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

        // return response()->noContent()->setStatusCode(200);
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
        $user = new User();
        $user->name = $data['name'];
        $user->firstname = null;
        $user->lastname = null;
        $user->email = $data['email'];
        $user->provider = Config::get('constants.provider.service');
        $user->password = Hash::make($data['password']);
        $user->save();

        return $user;
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
