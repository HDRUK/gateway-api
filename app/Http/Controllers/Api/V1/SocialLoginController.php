<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use App\Models\AuthorisationCode;
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
     * @OA\Get(
     *    path="/api/v1/auth/{provider}",
     *    operationId="login",
     *    tags={"Authentication"},
     *    summary="SocialLoginController@login",
     *    description="Login with Google / Linkedin with OpenId / Azure",
     *    @OA\Parameter(
     *       name="provider",
     *       in="path",
     *       description="google, linkedin, azure",
     *       required=true,
     *       example="google",
     *       @OA\Schema(
     *          type="string",
     *          description="provider",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=302,
     *       description="redirect to main page",
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *    ),
     * )
     * 
     * redirect to google authorization page
     * 
     * @param Request $request
     * @param string $provider
     * @return mixed
     */
    public function login(Request $request, string $provider): mixed
    {
        if ($provider === 'linkedin') {
            $provider = 'linkedin-openid';
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * @OA\Get(
     *    path="/api/v1/auth/{provider}/callback",
     *    operationId="login-callback",
     *    tags={"Authentication"},
     *    summary="SocialLoginController@callback",
     *    description="Login with Google / Linkedin with OpenId / Azure",
     *    @OA\Parameter(
     *       name="provider",
     *       in="path",
     *       description="google, linkedin with openid, azure",
     *       required=true,
     *       example="google",
     *       @OA\Schema(
     *          type="string",
     *          description="provider",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=302,
     *       description="redirect to main page",
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *    ),
     * )
     * 
     * 
     * redirect to front end page with token
     * 
     * @param Request $request
     * @param string $provider
     * @return mixed
     */
    public function callback(Request $request, string $provider): mixed
    {
        try {
            if ($provider === 'linkedin') {
                $provider = 'linkedin-openid';
            }

            $socialUser = Socialite::driver($provider)->user();

            $socialUserDetails = [];
            switch ($provider) {
                case 'google':
                    $socialUserDetails = $this->googleResponse($socialUser, $provider);
                    break;

                case 'linkedin-openid':
                    $socialUserDetails = $this->linkedinOpenIdResponse($socialUser, $provider);
                    break;

                case 'azure':
                    $socialUserDetails = $this->azureResponse($socialUser, $provider);
                    break;
            }

            $user = User::where([
                'email' => $socialUserDetails['email'],
                'provider' => $provider,
            ])->first();

            if (!$user) {
                $user = $this->saveUser($socialUserDetails);
            } else {
                $user = $this->updateUser($user, $socialUserDetails);
            }

            $jwt = $this->createJwt($user);

            $cookies = [
                Cookie::make('token', $jwt),
            ];
            return redirect()->away(env('GATEWAY_URL'))->withCookies($cookies);
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
     * Uniform response from LinkedIn using OpenID Connect
     *
     * @param object $data
     * @param string $provider
     * @return array
     */
    private function linkedinOpenIdResponse(object $data, string $provider): array
    {
        return [
            'providerid' => (string) $data->getId(),
            'name' => (string) $data->getName(),
            'firstname' => (string) $data->user['given_name'],
            'lastname' => (string) $data->user['family_name'],
            'email' => (string) $data->getEmail(),
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
        $currentTime = CarbonImmutable::now();
        $expireTime = $currentTime->addSeconds(env('JWT_EXPIRATION'));

        $arrayClaims = [
            'iss' => (string) env('APP_URL'),
            'sub' => (string) $user['name'],
            'aud' => (string) env('APP_NAME'),
            'iat' => (string) strtotime($currentTime),
            'nbf' => (string) strtotime($currentTime),
            'exp' => (string) strtotime($expireTime),
            'jti' => (string) env('JWT_SECRET'),
            'user' => $user,
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
