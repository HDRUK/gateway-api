<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Cookie;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\JwtController;
use Laravel\Socialite\Facades\Socialite;

class LoginLinkedinController extends Controller
{
    private JwtController $jwt;

    /**
     * constructor
     */
    public function __construct(JwtController $jwt)
    {
        $this->jwt = $jwt;
    }

    public function linkedin()
    {
        return Socialite::driver(Config::get('constants.provider.linkedin'))->stateless()->redirect();
    }

    public function linkedinRedirect()
    {
        try {
            $userLinkedin = Socialite::driver(Config::get('constants.provider.linkedin'))->stateless()->user();
            $userDetailsFromLinkedin = [
                'providerid' => $userLinkedin->getId(),
                'name' => $userLinkedin->getName(),
                'firstname' => $this->fetchFirstNameAndLastName($userLinkedin->user['firstName']),
                'lastname' => $this->fetchFirstNameAndLastName($userLinkedin->user['lastName']),
                'email' => $userLinkedin->getEmail(),
                'provider' => Config::get('constants.provider.linkedin'),
                'password' => Hash::make(json_encode($userLinkedin)),
            ];

            $user = User::where([
                'email' => $userLinkedin->getEmail(),
                'provider' => Config::get('constants.provider.linkedin'),
            ])->first();

            if (!$user) {
                $user = $this->saveUser($userDetailsFromLinkedin);
            } else {
                $user = $this->updateUser($user, $userDetailsFromLinkedin);
            }

            $jwt = $this->createJwt($user);

            $cookie = Cookie::make('token', $jwt);
            return redirect(env('GATEWAY_URL'), 302)->withCookie($cookie);

        } catch (Exception $e) {
            throw new Exception("Error Linkedin Processing Request", 1);
        }
    }

    /**
     * extract first name and last name from array
     * 
     * @param array $value
     * @return string
     */
    private function fetchFirstNameAndLastName(array $value): string
    {

        $country = $value['preferredLocale']['country'];
        $language = $value['preferredLocale']['language'];
        $keyName = strtolower($language) . "_" . strtoupper($country);

        $return = $value['localized'][$keyName];

        return $return;
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
