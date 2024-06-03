<?php

namespace App\Http\Controllers\Api\V1;

session_start();

use Auditor;
use Config;
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
use Jumbojett\OpenIDConnectClient;

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
     *    @OA\Parameter(
     *       name="redirect",
     *       in="redirect",
     *       description="url to redirect to",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="string",
     *          description="redirect",
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
        $redirectUrl = env('GATEWAY_URL');
        if($request->has("redirect")){
            $redirectUrl = $redirectUrl . $request->query('redirect');
        }

        session(['redirectUrl' => $redirectUrl]);
        $CONFIG['http.cookie.samesite'] = 'None';
        // session(['same_site' => 'none']);

        if ($provider === 'openathens') {
            return redirect()->action(
                [SocialLoginController::class, 'callback'], 
                ['provider' => 'openathens']
            );
        } else {
            return Socialite::driver($provider)
                ->redirect();
        }
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
        // session(['same_site' => 'none']);
        $CONFIG['http.cookie.samesite'] = 'None';
        try {
            if ($provider === 'linkedin') {
                $provider = 'linkedin-openid';
            }
            if ($provider === 'openathens') {
                $oidc = new OpenIDConnectClient(
                    Config::get('services.openathens.issuer'),
                    Config::get('services.openathens.client_id'),
                    Config::get('services.openathens.client_secret')
                );
                var_dump('client defined');
                $oidc->addScope(array('openid'));
                $oidc->setAllowImplicitFlow(true);
                $oidc->addAuthParam(array('response_mode' => 'form_post'));
                $oidc->authenticate();
                var_dump('oidc authenticated');
                $socialUser = $oidc->requestUserInfo();
                var_dump('social user retrieved');
                $socialUserDetails = $this->openathensResponse($socialUser, $provider);
                var_dump('social use details');
            } else {
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

            Auditor::log([
                'target_user_id' => $user->id,
                'action_type' => 'LOGIN',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "User " . $user->id . " with login through " . $user->provider . " has been connected",
            ]);

            $cookies = [
                Cookie::make('token', $jwt),
            ];
            $redirectUrl = session('redirectUrl');
            return redirect()->away($redirectUrl)->withCookies($cookies);
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
     * Uniform response from OpenAthens
     *
     * @param object $data
     * @param string $provider
     * @return array
     */
    private function openathensResponse(object $data, string $provider): array
    {
        return [
            'providerid' => $data['sub'],
            'name' => $data['name'],
            'firstname' => $data['given_name'],
            'lastname' => $data['family_name'],
            'email' => $data['email'],
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
