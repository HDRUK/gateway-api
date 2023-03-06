<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Cookie;
use Carbon\Carbon;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\JwtController;
use Laravel\Socialite\Facades\Socialite;

class LoginGoogleController extends Controller
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
     * redirect to google authorization page
     * 
     * @return mixed
     */
    public function google(): mixed
    {
        return Socialite::driver(Config::get('constants.provider.google'))->stateless()->redirect();
    }

    /**
     * redirect to front end page with token
     * 
     * @return mixed
     */
    public function googleRedirect()
    {
        $userGoogle = Socialite::driver(Config::get('constants.provider.google'))->stateless()->user();

        $userDetailsFromGoogle = [
            'providerid' => $userGoogle->getId(),
            'name' => $userGoogle->getName(),
            'firstname' => $userGoogle->offsetGet('given_name'),
            'lastname' => $userGoogle->offsetGet('family_name'),
            'email' => $userGoogle->getEmail(),
            'provider' => Config::get('constants.provider.google'),
            'password' => Hash::make(json_encode($userGoogle)),
        ];

        $user = User::where([
            'email' => $userGoogle->getEmail(),
            'provider' => Config::get('constants.provider.google'),
        ])->first();
        
        if (!$user) {
            $user = $this->saveUser($userDetailsFromGoogle);
        } else {
            $user = $this->updateUser($user, $userDetailsFromGoogle);
        }

        $jwt = $this->createJwt($user);

        $cookie = Cookie::make('token', $jwt);
        return redirect(env('GATEWAY_URL'), 302)->withCookie($cookie);
    }

    /**
     * update user in database
     */
    private function updateUser($user, $data)
    {
        $user->providerid = $data['providerid'];
        $user->name = $data['name'];
        $user->firstname = $data['firstname'];
        $user->lastname = $data['lastname'];
        $user->email = $data['email'];
        $user->provider = $data['provider'];
        $user->password = $data['password'];
        $user->update();

        return $user;
    }

    /**
     * save user in database
     */
    private function saveUser($data)
    {
        $user = new User();
        $user->providerid = $data['providerid'];
        $user->name = $data['name'];
        $user->firstname = $data['firstname'];
        $user->lastname = $data['lastname'];
        $user->email = $data['email'];
        $user->provider = $data['provider'];
        $user->password = $data['password'];
        $user->save();

        return $user;
    }

    /**
     * create JWT token
     */
    private function createJwt($user)
    {
        $currentTime = Carbon::now();

        $arrayClaims = [
            'iss' => (string) env('APP_URL'),
            'sub' => (string) $user['fullName'],
            'aud' => (string) env('APP_NAME'),
            'iat' => (string) strtotime($currentTime),
            'nbf' => (string) strtotime($currentTime),
            'exp' => (string) strtotime($currentTime->addSeconds(env('JWT_EXPIRATION'))),
            'jti' => (string) env('JWT_SECRET'),
            'user' => $user,
        ];

        $this->jwt->setPayload($arrayClaims);
        $jwt = $this->jwt->create();
        return $jwt;
    }
}
