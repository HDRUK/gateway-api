<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\JwtController;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    /**
     * Jwt Constroller
     *
     * @var JwtController
     */
    private JwtController $jwt;

    /**
     * Constructor
     *
     * @param JwtController $jwt
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
    public function login(Request $request, $provider): mixed
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * redirect to front end page with token
     * 
     * @param Request $request
     * @param string $provider
     * @return mixed
     */
    public function callback(Request $request, string $provider): mixed
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            $socialUserDetails = [];
            switch ($provider) {
                case 'google':
                    $socialUserDetails = $this->googleResponse($socialUser, $provider);
                    break;

                case 'linkedin':
                    $socialUserDetails = $this->linkedinResponse($socialUser, $provider);
                    break;

                case 'azure':
                    $socialUserDetails = $this->azureResponse($socialUser, $provider);
                    break;
            }

            $user = User::where([
                'email' => $socialUser->getEmail(),
                'provider' => $provider,
            ])->first();

            if (!$user) {
                $user = $this->saveUser($socialUserDetails);
            } else {
                $user = $this->updateUser($user, $socialUserDetails);
            }

            $jwt = $this->createJwt($user);

            $cookie = Cookie::make('token', $jwt);
            return redirect(env('GATEWAY_URL'), 302)->withCookie($cookie);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Uniform response from Google
     *
     * @param object $data
     * @param string $provider
     * @return array
     */
    private function googleResponse(object $data, string $provider): array
    {
        return [
            'providerid' => $data->getId(),
            'name' => $data->getName(),
            'firstname' => $data->offsetGet('given_name'),
            'lastname' => $data->offsetGet('family_name'),
            'email' => $data->getEmail(),
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
    }

    /**
     * Uniform response from LinkedIn
     *
     * @param object $data
     * @param string $provider
     * @return array
     */
    private function linkedinResponse(object $data, string $provider): array
    {
        return [
            'providerid' => $data->getId(),
            'name' => $data->getName(),
            'firstname' => $this->fetchFirstNameAndLastName($data->user['firstName']),
            'lastname' => $this->fetchFirstNameAndLastName($data->user['lastName']),
            'email' => $data->getEmail(),
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
    }

    /**
     * Uniform response from Azure
     *
     * @param object $data
     * @param string $provider
     * @return array
     */
    private function azureResponse(object $data, string $provider): array
    {
        $emailAddress = $data['mail'] ? $data['mail'] : $data->getEmail();
        return [
            'providerid' => $data->getId(),
            'name' => $data->getName(),
            'firstname' => $data->offsetGet('givenName'),
            'lastname' => $data->offsetGet('surname'),
            'email' => $emailAddress,
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
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
     * 
     * @param User $user
     * @param array $data
     * @return User
     */
    private function updateUser(User $user, array $data): User
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
     * 
     * @param array $value
     * @return User
     */
    private function saveUser(array $value): User
    {
        $user = new User();
        $user->providerid = $value['providerid'];
        $user->name = $value['name'];
        $user->firstname = $value['firstname'];
        $user->lastname = $value['lastname'];
        $user->email = $value['email'];
        $user->provider = $value['provider'];
        $user->password = $value['password'];
        $user->save();

        return $user;
    }

    /**
     * create JWT token
     * 
     * @param User $user
     * @return string
     */
    private function createJwt($user): string
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
