<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
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
    *    path="/api/v1/auth/dta/{provider}",
    *    operationId="dta-login",
    *    tags={"Authentication"},
    *    summary="SocialLoginController@dtaLogin",
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
    public function dtaLogin(Request $request, string $provider): mixed
    {
        return $this->handleLogin($request, $provider, env('DTA_URL'), env('OPENATHENS_REDIRECT_DTA_URL'), true);
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
        return $this->handleLogin($request, $provider, env('GATEWAY_URL'), env('OPENATHENS_REDIRECT_URL'), false);

    }

    private function handleLogin(Request $request, string $provider, string $baseRedirectUrl, $openAthensRedirectUrl, $isDTA): mixed
    {


        $redirectUrl = $baseRedirectUrl;
        if ($request->has("redirect")) {
            $redirectUrl .= $request->query('redirect');
        }

        session(['redirectUrl' => $redirectUrl]);

        if (strtolower($provider) === 'openathens') {
            $provider = 'open-athens';

            if ($request->has('target_link_uri')) {
                session(['redirectUrl' => $request->query('target_link_uri')]);
            }

            $params = [
                'client_id' => Config::get('services.openathens.client_id'),
                'redirect_uri' => $openAthensRedirectUrl,
                'response_type' => 'code',
                'scope' => 'openid',
                'state' => bin2hex(random_bytes(16))
            ];
            $oaUrl = env('OPENATHENS_ISSUER_URL') . '/oidc/auth?' . http_build_query($params);

            return redirect()->away($oaUrl);
        } else {
            if (strtolower($provider) === 'linkedin') {
                $provider = 'linkedin-openid';
            }
            if ($isDTA) {
                $redirectUrl = config("services.$provider.redirect");
                $redirectUrl = str_replace('/api/v1/auth', '/api/v1/auth/dta', $redirectUrl);

                // config([
                //     "services.$provider.redirect" => "https://api.dev.dementia-trials-accelerator.org/api/v1/auth/dta/google/callback"
                // ]);

            }
            return Socialite::driver($provider)->redirect();

        }
    }


    /**
         * @OA\Get(
         *    path="/api/v1/auth/dta/{provider}/callback",
         *    operationId="dta-login-callback",
         *    tags={"Authentication"},
         *    summary="SocialLoginController@dtaCallback",
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
    public function dtaCallback(Request $request, string $provider): mixed
    {
        return $this->handleCallback($request, $provider, env('GATEWAY_URL'), env('OPENATHENS_REDIRECT_URL'), true);
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
        return $this->handleCallback($request, $provider, env('GATEWAY_URL'), env('OPENATHENS_REDIRECT_URL'), false);
    }
    private function handleCallback(Request $request, string $provider, string $baseRedirectUrl, string $openAthensRedirectUrl, $isDTA): mixed
    {
        $user = null;
        if ($isDTA) {
            $redirectUrl = config("services.$provider.redirect");
            $redirectUrl = str_replace('/api/v1/auth', '/api/v1/auth/dta', $redirectUrl);

            config([
                "services.$provider.redirect" => $redirectUrl
            ]);

        }
        try {
            if (strtolower($provider) === 'linkedin') {
                $provider = 'linkedin-openid';
            }
            if (strtolower($provider) === 'openathens') {
                $provider = 'open-athens';
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $input = $request->all();
                $code = array_key_exists('code', $input) ? $input['code'] : '';
                $_REQUEST['code'] = $code;
                $state = array_key_exists('state', $input) ? $input['state'] : '';
                $_REQUEST['state'] = $state;
                $_SESSION['openid_connect_state'] = $state;

                $oidc = new OpenIDConnectClient(
                    Config::get('services.openathens.issuer'),
                    Config::get('services.openathens.client_id'),
                    Config::get('services.openathens.client_secret')
                );
                $oidc->providerConfigParam([
                    'authorization_endpoint' => env('OPENATHENS_ISSUER_URL') . '/oidc/auth',
                    'jwks_uri' => env('OPENATHENS_ISSUER_URL') . '/oidc/jwks',
                    'token_endpoint' => env('OPENATHENS_ISSUER_URL') . '/oidc/token',
                    'userinfo_endpoint' => env('OPENATHENS_ISSUER_URL') . '/oidc/userinfo',
                ]);

                $oidc->setRedirectUrl($openAthensRedirectUrl);
                $oidc->authenticate();

                $response = $oidc->requestUserInfo();
                $socialUser = json_decode(json_encode($response), true);
                $socialUserDetails = $this->openathensResponse($socialUser, $provider);

                $user = User::where('providerid', $socialUserDetails['providerid'])->first();
            } else {
                $socialUser = Socialite::driver($provider)->user();

                $socialUserDetails = [];
                switch (strtolower($provider)) {
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
                $user = User::where('email', $socialUserDetails['email'])->first();
            }

            if (!$user) {
                $user = $this->saveUser($socialUserDetails, $provider);
            } else {
                $user = $this->updateUser($user, $socialUserDetails, $provider);
            }

            $jwt = $this->createJwt($user);

            Auditor::log([
                'target_user_id' => $user->id,
                'action_type' => 'LOGIN',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'User ' . $user->id . ' with login through ' . $user->provider . ' has been connected',
            ]);
            $cookies = [Cookie::make('token', $jwt)];

            if ($isDTA) {
                $cookies = [Cookie::make('token', $jwt, 0, '/', 'dev.dementia-trials-accelerator.org')];
            }


            if ($user['name'] === '' || $user['email'] === '') {
                return redirect()->away($baseRedirectUrl . '/account/profile')->withCookies($cookies);
            } else {
                $redirectUrl = session('redirectUrl');
                return redirect()->away($redirectUrl)->withCookies($cookies);
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

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
            'firstname' => $data->user['given_name'] ?? '',
            'lastname' => $data->user['family_name'] ?? '',
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
            'providerid' => (string)$data->getId(),
            'name' => (string)$data->getName(),
            'firstname' => (string)$data->user['given_name'],
            'lastname' => (string)$data->user['family_name'],
            'email' => (string)$data->getEmail(),
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
     * @param array $data
     * @param string $provider
     * @return array
     */
    private function openathensResponse(array $data, string $provider): array
    {
        $targetedId = is_array($data['eduPersonTargetedID']) ? $data['eduPersonTargetedID'][0] : $data['eduPersonTargetedID'];
        $affiliation = is_array($data['eduPersonScopedAffiliation']) ? $data['eduPersonScopedAffiliation'][0] : $data['eduPersonScopedAffiliation'];
        return [
            'providerid' => $targetedId,
            'name' => '',
            'firstname' => '',
            'lastname' => '',
            'email' => $targetedId . $affiliation,
            'provider' => $provider,
            'password' => Hash::make(json_encode($data)),
        ];
    }

    /**
     * update user in database
     *
     * @param User $user
     * @param array $data
     * @param string $provider
     * @return User
     */
    private function updateUser(User $user, array $data, string $provider): User
    {
        if ($provider == 'open-athens') {
            $user->providerid = $data['providerid'];
            $user->preferred_email = 'secondary';
            $user->update();
        } else {
            $user->providerid = $data['providerid'];
            $user->name = $data['name'];
            $user->firstname = $data['firstname'];
            $user->lastname = $data['lastname'];
            $user->email = $data['email'];
            $user->provider = $data['provider'];
            $user->password = $data['password'];
            $user->update();
        }

        return $user;
    }

    /**
     * save user in database
     *
     * @param array $value
     * @param string $provider
     * @return User
     */
    private function saveUser(array $value, string $provider): User
    {
        $user = new User();
        $user->providerid = $value['providerid'];
        $user->name = $value['name'];
        $user->firstname = $value['firstname'];
        $user->lastname = $value['lastname'];
        $user->email = $value['email'];
        $user->provider = $value['provider'];
        $user->password = $value['password'];
        if ($provider == 'open-athens') {
            $user->preferred_email = 'secondary';
        }
        $user->save();

        return $user;
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
